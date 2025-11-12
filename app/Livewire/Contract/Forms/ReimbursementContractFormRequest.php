<?php

namespace App\Livewire\Contract\Forms;

use Livewire\Attributes\Validate;

class ReimbursementContractFormRequest extends BaseContractFormRequest
{
    #[Validate('accepted')]
    public string $reimbursement_consent_text;

    public string $id_form = 'REIMBURSEMENT_FORM';
}
