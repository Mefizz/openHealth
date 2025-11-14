<?php

declare(strict_types=1);

namespace App\Livewire\Contract\Forms;

use Livewire\Attributes\Validate;

class CapitationContractFormRequest extends BaseContractFormRequest
{
    /**
     * Specific fields for Capitation contracts.
     */

    #[Validate('required|array')]
    public ?array $contractor_divisions = [];

    #[Validate('required|boolean')]
    public bool $external_contractor_flag = false;

    #[Validate([
        'external_contractors.*.legal_entity_id' => 'required|uuid',
        'external_contractors.*.contract.number' => 'required|string',
        'external_contractors.*.contract.issued_at' => 'required|date_format:Y-m-d',
        'external_contractors.*.contract.expires_at' => 'required|date_format:Y-m-d',
        'external_contractors.*.divisions' => 'required|array|min:1',
        'external_contractors.*.divisions.*.id' => 'required|uuid',
        'external_contractors.*.divisions.*.medical_service' => 'required|string',
    ])]
    public array $external_contractors = [];

    /**
     * Initialize form with default values for Capitation.
     */
    public function __construct($component, $propertyName)
    {
        parent::__construct($component, $propertyName);
        $this->id_form = 'PMD_1'; // As per your JSON example
    }

    /**
     * Build the final payload for Capitation.
     *
     * @param  array  $context  Contextual data (e.g., owner ID, dictionaries)
     * @return array
     */
    public function buildPayload(array $context): array
    {
        // 1. Get the base payload
        $data = parent::buildPayload($context);

        // 2. Add/Set Capitation-specific fields
        $data['id_form'] = $this->id_form;
        $data['consent_text'] = $context['dictionaries']['CAPITATION_CONTRACT_CONSENT_TEXT']['APPROVED'];

        // 3. Format external_contractors if they exist
        if (!empty($this->external_contractors)) {
            // This structure matches your JSON example
            $data['external_contractors'] = $this->external_contractors;
        } else {
            $data['external_contractors'] = [];
        }

        // 4. If no external contractors, remove the flag
        if (!$this->external_contractor_flag) {
            unset($data['external_contractors']);
        }

        // 5. Unset Reimbursement fields if they exist on the object
        unset($data['medical_programs']);

        return $data;
    }
}
