<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Livewire\Contract\Forms\BaseContractFormRequest;
use App\Livewire\Contract\Forms\CapitationContractFormRequest;
use App\Livewire\Contract\Forms\ReimbursementContractFormRequest;
use App\Models\Contract;
use App\Models\LegalEntity;
use Illuminate\View\View;
use RuntimeException;
use App\Enums\Contract\ContractStatus;

/**
 * This component handles ONLY the editing of an existing contract draft.
 * It follows the (implied) EmployeeEdit pattern.
 */
class ContractEdit extends AbstractContractFormManager
{
    /**
     * Mount the component, load existing data.
     */
    public function mount(LegalEntity $legalEntity, Contract $contract): void
    {
        $this->legalEntity = $legalEntity;
        $this->contract = $contract; // (EN) $contract is already loaded via route-model binding
        $this->contractId = $contract->id;
        $this->pageTitle = $contract->status === ContractStatus::NEW ? __('forms.addContract') : __('forms.editContract');

        // 1. Resolve the correct Form Object based on the contract's type
        $this->form = $this->resolveFormObject($contract->type);

        // 2. Set the model in the form, which fills all matching properties
        $this->form->setModel($contract);

        // 3. Load shared dependencies
        $this->loadDictionaries();
        $this->divisions = $this->legalEntity->getActiveDivisions();

        // 4. Sync external contractors from Form Object to our local list
        if ($this->form instanceof CapitationContractFormRequest) {
            $this->external_contractors_list = $this->form->external_contractors;
        }

        // 5. Reset modal state
        $this->resetExternalContractorForm();
    }

    /**
     * Implements the abstract method for UPDATING a draft.
     */
    protected function handleDraftPersistence(): Contract
    {
        // Get all validated data from the Form Object
        $preparedData = $this->form->getPreparedData();

        // Update the local contract instance
        $this->contract->fill($preparedData);
        $this->contract->save();

        // Return the fresh instance
        return $this->contract->fresh();
    }

    /**
     * Factory method to resolve the correct Form Object class.
     */
    private function resolveFormObject(string $type): BaseContractFormRequest
    {
        $formClass = match ($type) {
            'capitation' => CapitationContractFormRequest::class,
            'reimbursement' => ReimbursementContractFormRequest::class,
            default => throw new RuntimeException("Unknown contract type: $type"),
        };

        return new $formClass($this, 'form');
    }

    /**
     * Renders the dynamic form view.
     */
    public function render(): View
    {
        // This can be the same view as ContractCreate uses.
        return view('livewire.contract.contract-form-wrapper');
    }
}
