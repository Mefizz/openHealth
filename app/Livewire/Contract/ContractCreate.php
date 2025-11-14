<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Enums\Contract\ContractStatus;
use App\Livewire\Contract\Forms\BaseContractFormRequest;
use App\Livewire\Contract\Forms\CapitationContractFormRequest;
use App\Livewire\Contract\Forms\ReimbursementContractFormRequest;
use App\Models\Contract;
use App\Models\LegalEntity;
use Illuminate\Support\Str;
use Illuminate\View\View;
use RuntimeException;

/**
 * This component handles ONLY the creation of a new contract draft.
 * It follows the EmployeeCreate pattern.
 */
class ContractCreate extends AbstractContractFormManager
{
    public string $type = '';

    public function mount(LegalEntity $legalEntity): void
    {
        $this->legalEntity = $legalEntity;
        $this->type = request()->query('type', 'capitation');
        $this->pageTitle = __('forms.addContract');

        // 1. Resolve the correct Form Object
        $this->form = $this->resolveFormObject($this->type);

        // 2. Load shared dependencies
        $this->loadDictionaries();
        $this->divisions = $this->legalEntity->getActiveDivisions();

        // 3. Reset modal state
        $this->resetExternalContractorForm();

        // 4. Set default values for the form
        $this->form->start_date = now()->format('Y-m-d');
        $this->form->end_date = now()->addYear()->format('Y-m-d');
    }

    /**
     * Implements the abstract method for CREATING a draft.
     */
    protected function handleDraftPersistence(): Contract
    {
        // Get all validated data from the Form Object
        $preparedData = $this->form->getPreparedData();

        $contract = new Contract();
        $contract->fill($preparedData);

        $contract->uuid = (string) Str::uuid();
        $contract->type = $this->type;
        $contract->status = ContractStatus::NEW;
        $contract->legal_entity_id = $this->legalEntity->id;
        $contract->contractor_payment_details = $this->form->contractor_payment_details ?? ['bank_name' => '', 'payer_account' => '', 'mfo' => ''];
        $contract->contractor_base = $this->form->contractor_base ?? '...';
        $contract->nhs_signer_base = '...';
        $contract->issue_city = '...';
        $contract->nhs_contract_price = 0;
        $contract->nhs_payment_method = '...';
        $contract->contract_number = 'NEW-' . date('Ymd-His');
        $contract->contractor_rmsp_amount = '...';

        $contract->save();

        return $contract;
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

    public function render(): View
    {
        return view('livewire.contract.contract-form-wrapper');
    }
}
