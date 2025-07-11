<?php

namespace App\Livewire\Employee;

use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\WithPagination;

class EmployeeRequestIndex extends EmployeeComponent
{
    use WithPagination;

    // --- Component State for UI ---
    public string $search = '';
    public string $status = '';
    public array $filter = [];

    // --- State for Delete Draft Modal ---
    public bool $showDeleteModal = false;
    public ?int $requestToDeleteId = null;
    public ?string $deleteRequestName = null;
    public string $deleteRequestText = '';

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

    #[Computed]
    public function parties(): LengthAwarePaginator
    {
        $legalEntityId = $this->legalEntity->id;

        $query = Party::query()
            ->whereHas('employeeRequests', function ($q) use ($legalEntityId) {
                $q->where('legal_entity_id', $legalEntityId);
            })
            ->with([
                       'phones',
                       'employeeRequests' => function ($q) use ($legalEntityId) {
                           $q->where('legal_entity_id', $legalEntityId)->with('division');
                       },
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

    public function resetFilters(): void
    {
        $this->reset(['filter', 'status', 'search']);
        $this->resetPage();
    }

    public function confirmRequestDeletion(int $id): void
    {
        $request = EmployeeRequest::with('party')->find($id);
        if (!$request || $request->uuid) return;

        $this->requestToDeleteId = $id;
        $this->deleteRequestName = $request->party->fullName ?? 'співробітника';
        $this->deleteRequestText = 'Ви впевнені, що хочете видалити чернетку?';
        $this->showDeleteModal = true;
    }

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

    public function render()
    {
        return view('livewire.employee.employee-request-index', [
            'parties' => $this->parties,
            'dictionaries' => $this->dictionaries,
        ]);
    }
}
