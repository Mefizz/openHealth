<?php

namespace App\Livewire\Employee;

use App\Traits\FormTrait;
use Livewire\Component;
use App\Livewire\Employee\Forms\EmployeeForm as Form;

abstract class EmployeeComponent extends Component
{
    public Form $form;
    public bool $isPersonalDataLocked = false;

    use FormTrait {
        getDictionary as traitGetDictionary;
    }

    public ?array $dictionaryNames = [
        'PHONE_TYPE', 'COUNTRY', 'SETTLEMENT_TYPE', 'SPECIALITY_TYPE', 'DIVISION_TYPE',
        'SPECIALITY_LEVEL', 'GENDER', 'QUALIFICATION_TYPE', 'SCIENCE_DEGREE', 'DOCUMENT_TYPE',
        'SPEC_QUALIFICATION_TYPE', 'EMPLOYEE_TYPE', 'POSITION', 'EDUCATION_DEGREE', 'DIVISION'
    ];

    public ?array $dictionaries = [];
    public array $employeeTypePosition = [];

    /**
     * This is the method that child components will call.
     * It loads all necessary dictionaries for the forms.
     */
    public function loadDictionaries(): void
    {
        $this->getDictionary();
    }

    /**
     * We override the getDictionary method from the trait to add our custom logic.
     */
    protected function getDictionary(): void
    {
        // First, call the original method from the trait
        $this->traitGetDictionary();

        // Then, add your custom filtering logic for roles
        if (legalEntity()) {
            $this->dictionaries['EMPLOYEE_TYPE'] = $this->getDictionariesFields(
                config('ehealth.legal_entity_type.' . legalEntity()->type . '.roles'),
                'EMPLOYEE_TYPE'
            );
        }
    }
}
