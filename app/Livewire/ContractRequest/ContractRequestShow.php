<?php

declare(strict_types=1);

namespace App\Livewire\ContractRequest;

use App\Models\Contracts\ContractRequest;
use App\Models\LegalEntity;
use Livewire\Component;

class ContractRequestShow extends Component
{
    public ContractRequest $contractRequest;

    public function mount(LegalEntity $legalEntity, string $contract): void
    {
        // Find the entry by UUID
        $this->contractRequest = ContractRequest::where('uuid', $contract)->firstOrFail();
    }

    public function render()
    {
        // Pass 'contract' to the template so that there is no Undefined variable error
        return view('livewire.contract-request.contract-request-show', [
            'contract' => $this->contractRequest
        ]);
    }
}
