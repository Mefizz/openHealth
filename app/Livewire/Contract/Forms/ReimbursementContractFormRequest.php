<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

use Livewire\Attributes\Validate;

class ReimbursementContractFormRequest extends BaseContractFormRequest
{
    /**
     * Specific fields for Reimbursement contracts.
     */
    #[Validate('required|array|min:1')]
    public array $medical_programs = [];

    /**
     * Initialize form with default values for Reimbursement.
     */
    public function __construct($component, $propertyName)
    {
        parent::__construct($component, $propertyName);
        $this->id_form = 'PMD_1'; // TODO: Your JSON says PMD_1, but this seems wrong. Update if needed.
        // This should probably be a different value, e.g., 'REIMBURSEMENT'.
    }

    /**
     * Build the final payload for Reimbursement.
     *
     * @param  array  $context  Contextual data (e.g., owner ID, dictionaries)
     * @return array
     */
    public function buildPayload(array $context): array
    {
        // 1. Get the base payload
        $data = parent::buildPayload($context);

        // 2. Add/Set Reimbursement-specific fields
        $data['id_form'] = $this->id_form;
        $data['consent_text'] = $context['dictionaries']['REIMBURSEMENT_CONTRACT_CONSENT_TEXT']['APPROVED'];
        $data['medical_programs'] = $this->medical_programs;

        // 3. Unset Capitation fields
        unset($data['contractor_divisions'], $data['external_contractor_flag'], $data['external_contractors']);

        return $data;
    }
}
