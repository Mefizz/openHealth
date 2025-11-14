<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use AllowDynamicProperties;
use App\Models\Contract;
use App\Models\LegalEntity;
use Illuminate\View\View;

/**
 * Show component, extends ContractComponent to get dictionaries.
 */
#[AllowDynamicProperties]
class ContractShow extends ContractComponent
{
    public Contract $contract;

    public function mount(LegalEntity $legalEntity, Contract $contract): void
    {
        $this->legalEntity = $legalEntity;
        $this->contract = $contract;
        $this->loadDictionaries();
    }

    public function render(): View
    {
        return view('livewire.contract.contract-show');
    }
}
