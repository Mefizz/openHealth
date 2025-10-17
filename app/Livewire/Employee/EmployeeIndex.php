<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Classes\eHealth\EHealth;
use App\Enums\Employee\RequestStatus;
use App\Jobs\EmployeeSync;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Notifications\EmployeeSyncCompleted;
use App\Notifications\SyncNotification;
use DB;
use Illuminate\Bus\Batch;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Crypt;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;
use Spatie\Permission\PermissionRegistrar;
use Throwable;

#[AllowDynamicProperties]
class EmployeeIndex extends EmployeeComponent
{
    use WithPagination;

    public string $search = '';
    public array $status = ['APPROVED', 'NEW', 'DISMISSED'];
    public array $filter = [
        'phone' => '', 'email' => '', 'role' => '', 'position' => '', 'division_id' => ''
    ];

    // --- State for Modals ---
    public bool $showDeactivateModal = false;
    public ?int $employeeToDeactivateId = null;
    public ?string $employeeToDeactivateName = null;
    public bool $showDeleteModal = false;
    public ?int $requestToDeleteId = null;
    public ?string $deleteRequestName = null;
    public int $legalEntityId;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->legalEntityId = $legalEntity->id;
        $this->loadDivisions($legalEntity);
        $this->loadDictionaries();
        $this->status = collect($this->status)->map(fn($s) => is_object($s) ? $s->value : $s)->all();
    }

    #[Computed]
    public function legalEntity(): LegalEntity
    {
        return LegalEntity::find($this->legalEntityId);
    }

    /**
     * Hook that automatically resets the position filter when the role filter changes.
     */
    public function updatedFilterRole(): void
    {
        $this->filter['position'] = '';
        $this->resetPage();
    }

    public function applyFilters(): void
    {
        $this->resetPage();
    }

    public function resetFilters(): void
    {
        $this->reset(['filter', 'status', 'search']);
        $this->status = ['APPROVED', 'NEW', 'DISMISSED'];
        $this->resetPage();
    }

    /**
     * The main computed property. It fetches all positions, groups them by party,
     * and returns a paginated collection of these groups.
     */
    #[Computed]
    public function parties(): LengthAwarePaginator
    {
        $employeesQuery = Employee::query()
            ->where('legal_entity_id', $this->legalEntity->id)
            ->with(['party.phones', 'division']);
        $requestsQuery = EmployeeRequest::query()
            ->where('legal_entity_id', $this->legalEntity->id)
            ->whereIn('status', [RequestStatus::NEW, RequestStatus::SIGNED])
            ->with(['party.phones', 'revision', 'division']);

        $this->applyAllFilters($employeesQuery, Employee::class);
        $this->applyAllFilters($requestsQuery, EmployeeRequest::class);

        $allItems = $employeesQuery->get()->merge($requestsQuery->get());
        $groupedByParty = $allItems->groupBy('party_id');
        $draftsForNewParties = $groupedByParty->pull(null); // Pull returns array|Collection|null

        $partyIds = $groupedByParty->keys()->filter()->all();
        $parties = !empty($partyIds) ? Party::with('phones')->find($partyIds)->keyBy('id') : collect();

        $finalList = new Collection();
        // Use collect() wrapper for safety
        foreach (collect($groupedByParty) as $partyId => $positions) {
            $party = $parties->get($partyId);
            if ($party) {
                $party->all_positions = $positions->sortByDesc('created_at');
                $finalList->push($party);
            }
        }

        $draftList = new Collection();
        // This ensures that even if $draftsForNewParties is an array, it will be handled correctly.
        foreach (collect($draftsForNewParties)->flatten() as $request) {
            if (!($request instanceof EmployeeRequest)) {
                continue;
            }

            $partyData = $request->revision->data['party'] ?? [];
            $fakeParty = new Party();
            $fakeParty->id = 'draft_' . $request->id;
            $fakeParty->last_name = $partyData['lastName'] ?? 'New';
            $fakeParty->first_name = $partyData['firstName'] ?? 'Draft';
            $fakeParty->email = $partyData['email'] ?? null;
            $fakeParty->setRelation('phones', collect());
            $fakeParty->all_positions = collect([$request]);
            $draftList->push($fakeParty);
        }

        $combinedList = $draftList->merge($finalList)->sortBy(fn($p) => str_starts_with((string)$p->id, 'draft') ? 0 : 1);

        $perPage = 10;
        $currentPage = $this->getPage();
        $currentPageItems = $combinedList->slice(($currentPage - 1) * $perPage, $perPage)->values();

        return new LengthAwarePaginator(
            $currentPageItems,
            $combinedList->count(),
            $perPage,
            $currentPage,
            ['path' => Paginator::resolveCurrentPath()]
        );
    }

    /**
     * A new universal method to apply all filters to a query.
     */
    private function applyAllFilters(Builder $query, string $modelClass): void
    {
        // Filters for the position itself (status, division, role, position)
        if (!empty($this->status)) {
            $query->whereIn('status', $this->status);
        }
        if (!empty($this->filter['division_id'])) {
            $query->where('division_id', $this->filter['division_id']);
        }
        if (!empty($this->filter['role'])) {
            $query->where('employee_type', $this->filter['role']);
        }
        if (!empty($this->filter['position'])) {
            $query->where('position', $this->filter['position']);
        }

        // Filters for the person (full name, email, phone)
        $query->where(function (Builder $subQuery) use ($modelClass) {

            if (!empty($this->search)) {
                $searchTerm = '%' . mb_strtolower($this->search, 'UTF-8') . '%';

                $subQuery->where(function (Builder $nameQuery) use ($searchTerm, $modelClass) {
                    $nameQuery->whereHas('party', function (Builder $partyQuery) use ($searchTerm) {
                        $partyQuery->where(DB::raw("LOWER(CONCAT(last_name, ' ', first_name, ' ', second_name))"), 'LIKE', $searchTerm);
                    });

                    if ($modelClass === EmployeeRequest::class) {
                        $nameQuery->orWhere(function (Builder $revisionQuery) use ($searchTerm) {
                            $revisionQuery->whereNull('party_id')
                                ->whereHas('revision', function (Builder $q) use ($searchTerm) {
                                    // **UNIVERSAL SYNTAX:** Using LOWER() for JSON fields
                                    $q->where(DB::raw("LOWER(data->'party'->>'last_name')"), 'LIKE', $searchTerm)
                                        ->orWhere(DB::raw("LOWER(data->'party'->>'first_name')"), 'LIKE', $searchTerm)
                                        ->orWhere(DB::raw("LOWER(data->'party'->>'second_name')"), 'LIKE', $searchTerm);
                                });
                        });
                    }
                });
            }

            if (!empty($this->filter['email'])) {
                $emailTerm = '%' . mb_strtolower($this->filter['email'], 'UTF-8') . '%';

                $subQuery->where(function (Builder $emailQuery) use ($emailTerm, $modelClass) {
                    $emailQuery->whereHas('party', function (Builder $partyQuery) use ($emailTerm) {
                        $partyQuery->where(DB::raw('LOWER(email)'), 'LIKE', $emailTerm);
                    });

                    if ($modelClass === EmployeeRequest::class) {
                        $emailQuery->orWhere(function (Builder $revisionQuery) use ($emailTerm) {
                            $revisionQuery->whereNull('party_id')
                                ->whereHas('revision', function (Builder $q) use ($emailTerm) {
                                    $q->where(DB::raw("LOWER(data->'party'->>'email')"), 'LIKE', $emailTerm);
                                });
                        });
                    }
                });
            }

            if (!empty($this->filter['phone'])) {
                $subQuery->whereHas('party.phones', function (Builder $phoneQuery) {
                    $phoneQuery->where('number', 'LIKE', '%' . $this->filter['phone'] . '%');
                });
            }
        });
    }

    public function showModalDeactivate(int $id): void
    {
        $employee = Employee::with('party')->find($id);
        if (!$employee) {
            return;
        }
        $this->employeeToDeactivateName = $employee->party->fullName ?? __('employees.modals.deactivate.default_name');
        $this->employeeToDeactivateId = $id;
        $this->showDeactivateModal = true;
    }

    /**
     * Closes the deactivation modal and resets its state.
     */
    public function closeModal(): void
    {
        $this->showDeactivateModal = false;
        $this->reset(['employeeToDeactivateId', 'employeeToDeactivateName']);
    }

    /**
     * Performs the deactivation action.
     */
    public function deactivate(): void
    {
        $employee = Employee::find($this->employeeToDeactivateId);
        if (!$employee) {
            $this->closeModal();
            return;
        }
        try {
            $response = EHealth::employee()->deactivate($employee->uuid);
            if ($response->successful()) {
                $employee->update(['status' => 'DISMISSED', 'end_date' => Carbon::now()->format('Y-m-d')]);
                if ($user = $employee->user) {
                    if ($user->hasRole($employee->employee_type)) {
                        $user->removeRole($employee->employee_type);
                    }
                }
                $this->dispatch('flashMessage', ['message' => __('employees.dismissalSuccess'), 'type' => 'success']);
            } else {
                $this->dispatch('flashMessage', ['message' => __('employees.dismissalEhealthError'), 'type' => 'error']);
            }
        } catch (Throwable $e) {
            $this->dispatch('flashMessage', ['message' => __('employees.requestError', ['error' => $e->getMessage()]), 'type' => 'error']);
        }
        $this->closeModal();
    }

    /**
     * Triggers a manual, forced synchronization with E-Health.
     */
    public function sync(): void
    {
        $user = Auth::user();
        $legalEntity = $this->legalEntity;
        $token = session()->get(config('ehealth.api.oauth.bearer_token'));

        if (!$token) {
            $this->dispatch('flashMessage', ['message' => 'Active token for synchronization not found.', 'type' => 'error']);
            return;
        }

        $user->notify(new SyncNotification('employee', 'started'));
        $this->dispatch('flashMessage', ['message' => __('employees.sync.started'), 'type' => 'success']);

        Bus::batch([ new EmployeeSync(legalEntity: $legalEntity, isFirstLogin: true) ])
            ->name('Manual Employee Full Sync')
            ->withOption('legal_entity_id', $legalEntity->id)
            ->withOption('token', Crypt::encryptString($token))
            ->withOption('user', $user)
            ->then(function (Batch $batch) use ($user) {
                app(PermissionRegistrar::class)->forgetCachedPermissions();
                $message = __('employees.sync.completed_successfully', [
                    'processed' => $batch->processedJobs, 'total' => $batch->totalJobs,
                ]);
                $user->notify(new EmployeeSyncCompleted($message, 'success'));
            })->catch(function (Batch $batch, Throwable $e) use ($user) {
                Log::error('Manual employee sync batch failed.', ['batch_id' => $batch->id, 'exception' => $e]);
                $user->notify(new EmployeeSyncCompleted(__('employees.sync.failed'), 'error'));
            })
            ->onQueue('sync')
            ->dispatch();
    }

    public function confirmRequestDeletion(int $id): void
    {
        $request = EmployeeRequest::with('party')->find($id);
        if (!$request || $request->uuid) return;
        $this->requestToDeleteId = $id;
        $this->deleteRequestName = $request->party->fullName ?? __('employees.modals.delete_draft.default_name');
        $this->showDeleteModal = true;
    }

    public function deleteRequest(): void
    {
        $request = EmployeeRequest::find($this->requestToDeleteId);
        if ($request && !$request->uuid) {
            $request->delete();
            $this->dispatch('flashMessage', ['message' => __('employees.draft.delete_success'), 'type' => 'success']);
        }
        $this->showDeleteModal = false;
        $this->requestToDeleteId = null;
    }

    public function render(): object
    {
        return view('livewire.employee.employee-index', [
            'parties' => $this->parties,
            'dictionaries' => $this->dictionaries,
        ]);
    }
}
