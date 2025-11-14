<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Traits\FormTrait;
use Livewire\Component;

/**
 * Abstract base component for Contracts\
 * Handles shared dependencies, like loading dictionaries\
 */
abstract class ContractComponent extends Component
{
    use FormTrait {
        getDictionary as traitGetDictionary;
    }

    // Define all dictionaries needed for any type of contract
    public ?array $dictionaryNames = [
        'CONTRACT_TYPE',
        'CAPITATION_CONTRACT_CONSENT_TEXT',
        'REIMBURSEMENT_CONTRACT_CONSENT_TEXT',
        'SPECIALITY_TYPE',
    ];

    public ?array $dictionaries = [];

    /**
     * Public method to load dictionaries, called by child components.
     */
    public function loadDictionaries(): void
    {
        $this->getDictionary();
    }

    /**
     * The protected getDictionary method contains the implementation.
     */
    protected function getDictionary(): void
    {
        $this->traitGetDictionary();
    }
}
