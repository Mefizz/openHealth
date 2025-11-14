<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Enums\Contract\ContractStatus; // Assuming you have/create this Enum
use App\Models\Contract;
use App\Models\LegalEntity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\WithPagination;

class ContractIndex extends ContractComponent
{
    use WithPagination;

    public LegalEntity $legalEntity;

    #[Validate('required|string|in:capitation,reimbursement')]
    public string $contractType = '';

    public ?array $tableHeaders = ['UUID', 'Number', 'Type', 'Status', 'Dates', 'Actions']; // Simplified

    public function mount(LegalEntity $legalEntity): void
    {
        $this->legalEntity = $legalEntity;
        $this->loadDictionaries();
    }

    /**
     * Creates a new local contract draft and redirects to the edit page.
     * This is the "Create Local First" pattern.
     */
    public function createContract(): void
    {
        $this->validate();

        $contract = new Contract();
        $contract->uuid = (string) Str::uuid(); // Use a temp UUID
        $contract->type = $this->contractType;
        $contract->status = ContractStatus::NEW; // Use an Enum: 'NEW'
        $contract->legal_entity_id = $this->legalEntity->id;

        $contract->start_date = now()->format('Y-m-d');
        $contract->end_date = now()->addYear()->format('Y-m-d');
        $contract->contractor_payment_details = ['bank_name' => '', 'payer_account' => '', 'mfo' => ''];

        $contract->save();

        $this->dispatch('flashMessage', ['message' => 'Created local draft.', 'type' => 'success']);

        // Redirect to the edit page to fill out the form
        $this->redirect(route('contracts.edit', [
            'legalEntity' => $this->legalEntity->uuid,
            'contract' => $contract->uuid, // Use our local UUID for the route
        ]));
    }

    #[Computed]
    public function contracts(): LengthAwarePaginator
    {
        // This will now correctly fetch CapitationContract or ReimbursementContract models
        return $this->legalEntity
            ->contracts()
            ->paginate(config('pagination.per_page', 10));
    }

    public function render()
    {
        return view('livewire.contract.contract-index', [
            'contracts' => $this->contracts,
        ]);
    }
}
