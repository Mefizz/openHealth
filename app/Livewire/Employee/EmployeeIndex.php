<?php

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Classes\eHealth\Api\EmployeeApi;
use App\Enums\Status;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\BaseEmployee;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Repositories\EmployeeRepository;
use App\Traits\FormTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

#[AllowDynamicProperties]
class EmployeeIndex extends Component
{
    use FormTrait;
    use WithPagination;

    public Collection $employees;
//    public Collection $divisions;


    public string $status = '';
    public array $filter = [
        'phone' => '',
        'email' => '',
        'role' => '',
        'position' => '',
        'division_id' => '',
    ];

    // --- Component State ---
    public string      $search          = '';
    public string|bool $showModal       = false;
    public bool        $showDeleteModal = false;

    // --- Properties for Dismissal Modal ---
    public ?int $dismissed_id = null;
    public ?string $dismissal_employee_name = null;
    public string $dismiss_text = '';

    // --- Properties for Delete Draft Modal ---
    public ?int $requestToDeleteId = null;
    public ?string $deleteRequestName = null;
    public string $deleteRequestText = '';

    // --- Private properties ---
    private LegalEntity $legalEntity;

    public function boot(): void
    {
        $this->legalEntity = legalEntity();
    }

    public function mount(LegalEntity $legalEntity): void
    {
//        $this->divisions = $this->legalEntity->divisions()->get();
        $this->employees = new Collection();
        $this->employeeCacheKey = 'employees_cache_' . $this->legalEntity->id;
        $this->getDictionary();
    }

    #[Computed]
    public function parties(): LengthAwarePaginator
    {
        $legalEntityId = legalEntity()->id;

        $query = Party::query()
            ->whereHas('employees', fn($q) => $q->where('legal_entity_id', $legalEntityId))
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

        return $query->paginate(10);
    }

    public function updated($property): void
    {
        if (in_array($property, ['search', 'status']) || str_starts_with($property, 'filter.')) {
            $this->resetPage();
        }
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
        $employee = Employee::find($id);
        if (!$employee) return;

        $this->dismissal_employee_name = $employee->party->fullName ?? 'співробітника';
        $this->dismiss_text = __('employees.dismissalWarning');
        $this->dismissed_id = $employee->id;
        $this->showModal = true;
    }

    /**
     * Closes the dismissal modal.
     */
    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['dismissed_id', 'dismissal_employee_name', 'dismiss_text']);
    }

    #[On('refreshPage')]
    public function refreshPage(): void
    {
        $this->dispatch('$refresh');
    }

    public function tableHeaders(): void
    {
        $this->tableHeaders = [
            __('ID E-health '),
            __('ПІБ'),
            __('Телефон'),
            __('Email'),
            __('Посада'),
            __('Статус'),
            __('forms.action'),
        ];
    }

    public function sortEmployees($status): void
    {
        $this->status = $status;
        $this->getEmployees();
    }

    public function dismissed(int $employeeId): void
    {
        $employee = Employee::find($this->dismissed_id);
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

                $this->dispatchErrorMessage(__('employees.dismissalSuccess'), 'success');
            } else {
                $this->dispatchErrorMessage(__('employees.dismissalEhealthError'));
            }
        } catch (\Exception $e) {
            $this->dispatchErrorMessage(
                __('employees.requestError', ['error' => $e->getMessage()])
            );
        }

        $this->closeModal();
        $this->dispatch('flashMessage', ['message' => 'Співробітника успішно звільнено', 'type' => 'success']);
    }

    //TODO: Створити багато співробітників в статусі не підтверджено, створювати таблицю EmployeeRequest? перевірити Rate Limit
    public function getEmployeeRequestsList()
    {
        return EmployeeRequestApi::getEmployeeRequestsList();
    }

    /**
     * Syncs employees by fetching data from the EmployeeRequestApi and saving it using the employeeSyncService.
     *
     * @throws \Exception
     */
    public function getLastStoreId(): void
    {
        if (Cache::has($this->employeeCacheKey) && !empty(Cache::get($this->employeeCacheKey)) && is_array(Cache::get($this->employeeCacheKey))) {
            $this->storeId = array_key_last(Cache::get($this->employeeCacheKey));
        }
        $this->storeId++;
    }

    public function getEmployeesCache(): Collection
    {
        if (Cache::has($this->employeeCacheKey)) {
            return collect(Cache::get($this->employeeCacheKey))->map(function ($data) {
                $employee = new BaseEmployee()->forceFill($data['party']);
                $employee->party = new Party()->forceFill($data['party'] ?? []);
                $employee->party->phones = new Phone()->forceFill($data['party']['phones'] ?? []);
                return $employee;
            });
        }
        return collect();
    }

    public function getEmployees(): void
    {
        if ($this->status === 'APPROVED') {
            $this->employees = $this->legalEntity->employees()->get();
        } elseif ($this->status === 'NEW') {
            $this->employees = $this->legalEntity->employeesRequest()->get();
        } else {
            $this->employees = $this->getEmployeesCache();
        }
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

        $this->getEmployees();
    }

    /**
     * Prepares and shows the delete confirmation modal for a draft.
     */
    public function confirmRequestDeletion(int $id): void
    {
        $request = EmployeeRequest::with('party')->find($id);
        if (!$request || $request->uuid) return;

        $this->requestToDeleteId = $id;
        $this->deleteRequestName = $request->party->fullName ?? 'співробітника';
        $this->deleteRequestText = 'Ви впевнені, що хочете видалити чернетку для цього співробітника? Цю дію неможливо буде скасувати.';
        $this->showDeleteModal = true;
    }

    /**
     * Deletes the employee request draft.
     */
    public function deleteRequest(): void
    {
        $request = EmployeeRequest::find($this->requestToDeleteId);
        if ($request && !$request->uuid) {
            $request->delete();
            $this->dispatch('flashMessage', ['message' => 'Чернетку успішно видалено.', 'type' => 'success']);
        }
        $this->showDeleteModal = false;
        $this->requestToDeleteId = null;
    }


    private function dispatchErrorMessage(string $message, string $type = 'success', array $errors = []): void
    {
        $this->dispatch('flashMessage', [
            'message' => $message,
            'type'    => $type,
            'errors'  => $errors
        ]);
    }

    /**
     * Renders the component.
     */
    public function render()
    {
        return view('livewire.employee.employee-index', [
            'parties' => $this->parties,
            'dictionaries' => $this->dictionaries,
        ]);
    }
}
