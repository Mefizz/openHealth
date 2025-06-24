<?php

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Traits\FormTrait;
use Illuminate\Support\Collection;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;

#[AllowDynamicProperties]
class EmployeeRequestIndex extends Component
{
    use FormTrait;
    use WithPagination;

    // --- Component State ---
    public string $search = '';
    public bool $showDeleteModal = false;

    // --- Properties for Delete Draft Modal ---
    public ?int $requestToDeleteId = null;
    public ?string $deleteRequestName = null;
    public string $deleteRequestText = '';

    // --- Properties to prevent Blade errors (from dismissal modal) ---
    // These are not used here, but the Blade view expects them to exist.
    public string|bool $showModal    = false;

    private LegalEntity $legalEntity;

    public function boot(): void
    {
        $this->legalEntity = legalEntity();
    }

    public function mount(LegalEntity $legalEntity): void
    {
        //        $this->divisions = $this->legalEntity->divisions()->get();
        $this->employeeRequest = new Collection();
        $this->employeeRequestsCacheKey = 'employees_cache_' . $this->legalEntity->id;
        $this->getDictionary();
    }

    #[Computed]
    public function parties(): LengthAwarePaginator
    {
        $legalEntityId = $this->legalEntity->id;

        $query = Party::query()
            ->whereHas('employeeRequests', fn($q) => $q->where('legal_entity_id', $legalEntityId))
            ->with([
                       'phones',
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
