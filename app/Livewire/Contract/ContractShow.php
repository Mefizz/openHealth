<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Models\Contract;
use App\Models\LegalEntity;
use Illuminate\View\View;
use Livewire\Component;

class ContractShow extends Component
{
    public Contract $contract;
    public LegalEntity $legalEntity;

    public function mount(LegalEntity $legalEntity, Contract $contract): void
    {
        $this->legalEntity = $legalEntity;
        $this->contract = $contract;
    }

    public function render(): View
    {
        return view('livewire.contract.contract-show');
    }
}
