<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

class ReimbursementContractFormRequest extends BaseContractFormRequest
{
    public string $contractorOwnerId;

    public string $contractorBase;

    public string $previousRequestId;

    public array $medicalPrograms;

    public bool $consentText;

    public function rules(): array
    {
        return array_merge(parent::rules(), [
            'contractorBase' => ['required', 'string', 'max:255'],
            'previousRequestId' => ['required', 'uuid', 'exists:contracts,uuid'],
            'medicalPrograms' => ['required', 'array'],
            'consentText' => ['required', 'in:true']
        ]);
    }
}
