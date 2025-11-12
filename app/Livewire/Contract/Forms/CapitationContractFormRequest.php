<?php

namespace App\Livewire\Contract\Forms;

use Livewire\Attributes\Validate;

class CapitationContractFormRequest extends BaseContractFormRequest
{
    #[Validate('accepted')]
    public string $consent_text;

    public string $id_form = 'PMD_1';

    public function buildPayload(array $context): array
    {
        $data = parent::buildPayload($context);
        $data['consent_text'] = $context['dictionaries']['CAPITATION_CONTRACT_CONSENT_TEXT']['APPROVED'];

        return $data;
    }
}
