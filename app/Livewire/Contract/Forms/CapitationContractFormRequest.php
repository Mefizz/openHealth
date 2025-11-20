<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

class CapitationContractFormRequest extends BaseContractFormRequest
{
    public function rules(): array
    {
        return array_merge(parent::rules(), []);
    }
}
