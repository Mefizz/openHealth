<?php

namespace App\Livewire\Employee;

use App\Enums\Status;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class EmployeeIndex extends EmployeeComponent
{
    use WithPagination;

    // --- Component State for Filters ---
    public string $search = '';
    public string $status = '';
    public array  $filter = [
        'phone' => '',
        'email' => '',
        'role' => '',
        'position' => '',
    ];
    public ?array $dictionaries = [];

    // --- State for Dismissal Modal (Standardized Names) ---
    public bool $showDismissModal = false;
    public ?int $employeeToDismissId = null;
    public ?string $employeeToDismissName = null;

    private LegalEntity $legalEntity;

    public function boot(): void
    {
        $this->legalEntity = legalEntity();
        $this->loadDictionaries();
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status']) || str_starts_with($property, 'filter.')) {
            $this->resetPage();
        }
    }

    /**
     * Main computed property to fetch and filter parties.
     */
    #[Computed]
    public function parties(): LengthAwarePaginator
    {
        $legalEntityId = $this->legalEntity->id;

        $query = Party::query()
            ->where(function ($q) use ($legalEntityId) {
                $q->whereHas('employees', fn($sub) => $sub->where('legal_entity_id', $legalEntityId))
                    ->orWhereHas('employeeRequests', fn($sub) => $sub->where('legal_entity_id', $legalEntityId));
            })
            ->with([
                       'phones',
                       'employees' => fn($q) => $q->where('legal_entity_id', $legalEntityId)->with('division'),
                       'employeeRequests' => fn($q) => $q->where('legal_entity_id', $legalEntityId)->with('division'),
                   ]);

        if (!empty($this->search)) {
            $query->where(function($q) {
                $q->where('last_name', 'ilike', "%{$this->search}%")
                    ->orWhere('first_name', 'ilike', "%{$this->search}%")
                    ->orWhere('second_name', 'ilike', "%{$this->search}%");
            });
        }

        if (!empty($this->filter['phone'])) {
            $query->whereHas('phones', fn($q) => $q->where('number', 'like', '%' . $this->filter['phone'] . '%'));
        }
        if (!empty($this->filter['email'])) {
            $query->where('email', 'ilike', '%' . $this->filter['email'] . '%');
        }
        if (!empty($this->filter['role'])) {
            $query->whereHas('employees', fn($q) => $q->where('employee_type', $this->filter['role']));
        }
        if (!empty($this->filter['position'])) {
            $query->whereHas('employees', fn($q) => $q->where('position', $this->filter['position']));
        }
        if (!empty($this->status)) {
            $query->whereHas('employees', fn($q) => $q->where('status', $this->status));
        }

        return $query->paginate(10);
    }


    public function resetFilters(): void
    {
        $this->reset(['filter', 'status', 'search']);
        $this->resetPage();
    }

    /**
     * Prepares and shows the dismissal modal.
     */
    public function showModalDismissed(int $id): void
    {
        $employee = Employee::with('party')->find($id);
        if (!$employee) return;

        $this->employeeToDismissName = $employee->party->fullName ?? 'співробітника';
        $this->employeeToDismissId = $id;
        $this->showDismissModal = true;
    }

    /**
     * Closes the dismissal modal and resets its state.
     */
    public function closeModal(): void
    {
        $this->showDismissModal = false;
        $this->reset(['employeeToDismissId', 'employeeToDismissName']);
    }

    /**
     * Performs the dismissal action.
     */
    public function dismissed(): void
    {
        $employee = Employee::find($this->employeeToDismissId);
        if (!$employee) {
            $this->closeModal();
            return;
        }

        try {
            $response = EmployeeRequestApi::dismissedEmployeeRequest($employee->uuid);

            if (!empty($response)) {
                $employee->update(
                    [
                        'status'   => Status::DISMISSED->value,
                        'end_date' => Carbon::now()->format('Y-m-d'),
                    ]
                );
                $this->dispatch('flashMessage', ['message' => __('employees.dismissalSuccess'), 'type' => 'success']);
            } else {
                $this->dispatch('flashMessage', ['message' => __('employees.dismissalEhealthError'), 'type' => 'error']);
            }
        } catch (\Exception $e) {
            $this->dispatch('flashMessage', ['message' => __('employees.requestError', ['error' => $e->getMessage()]), 'type' => 'error']);
        }

        $this->closeModal();
    }

    public function syncEmployees(): void
    {
        $requests = EmployeeRequestApi::getEmployees($this->legalEntity->uuid);
        foreach ($requests as $request) {
            $response = EmployeeRequestApi::getEmployeeById($request['id']);
            $employeeResponse = schemaService()->setDataSchema($response, app(EmployeeApi::class))
                ->responseSchemaNormalize()
                ->replaceIdsKeysToUuid(['id', 'legalEntityId', 'divisionId', 'partyId'])
                ->snakeCaseKeys(true)
                ->getNormalizedData();
            app(EmployeeRepository::class)
                ->store($employeeResponse,
                        legalEntity(),
                        new Employee());
        }

        $this->dispatchErrorMessage(__('Співробітники успішно синхронізовано'));
    }

    private function dispatchErrorMessage(string $message, string $type = 'success', array $errors = []): void
    {
        $this->dispatch('flashMessage', [
            'message' => $message, 'type' => $type, 'errors' => $errors
        ]);
    }

    /**
     * Renders the component view.
     */
    public function render(): object
    {
        return view('livewire.employee.employee-index', [
            'parties' => $this->parties,
            'dictionaries' => $this->dictionaries,
        ]);
    }
}
