<?php

namespace App\Livewire\Employee;

use App\Models\LegalEntity;
use App\Repositories\EmployeeRepository;
use App\Traits\FormTrait;
use App\Traits\HandlesLegalEntity;
use App\Models\Employee\Employee as EmployeeModel;
use Livewire\Component;
use App\Livewire\Employee\Forms\EmployeeForm;
//use App\Livewire\Employee\EmployeeForm as Form;
use App\Classes\Cipher\Traits\Cipher;

class EmployeeComponent extends Component
{
    use Cipher, HandlesLegalEntity, FormTrait {
        HandlesLegalEntity::resolveLegalEntity as traitResolveLegalEntity;
        FormTrait::getDictionary as traitGetDictionary;
    }

//    public Form $form;
    public EmployeeForm $employeeRequest; // <-- це Livewire форма
    public ?int $employeeId = null;
    protected ?EmployeeModel $employee = null;

    /**
     * TODO remove when Repo class is implemented
     */
    protected EmployeeRepository $employeeRepository;

    /*
     * Legal entity instance associated with the logged in user
     */
    protected LegalEntity $legalEntity;

    /**
     * @var array|string[] Set dictionaries to load with the component
     */
    public ?array $dictionaryNames = [
        'PHONE_TYPE',
        'COUNTRY',
        'SETTLEMENT_TYPE',
        'SPECIALITY_TYPE',
        'DIVISION_TYPE',
        'SPECIALITY_LEVEL',
        'GENDER',
        'QUALIFICATION_TYPE',
        'SCIENCE_DEGREE',
        'DOCUMENT_TYPE',
        'SPEC_QUALIFICATION_TYPE',
        'EMPLOYEE_TYPE',
        'POSITION',
        'EDUCATION_DEGREE',
        'EMPLOYEE_TYPE',
    ];

    /*
     * Holds information about relation between employee type, e.g., ADMIN - administrator and position, like P5 - head of dept;
     * See config/ehealth.php employee_type for more details
     */
    public array $employeeTypePosition = [];

    public function mount(): void
    {
        $this->getDictionary();
        $this->legalEntity = auth()->user()->legalEntity;
//        $this->legalEntity = $this->traitResolveLegalEntity();
        $this->setCertificateAuthority();
        $this->employeeRequest = new EmployeeForm($this, 'employeeRequest');
    }

    public function boot(EmployeeRepository $employeeRepository): void
    {
        $this->employeeRepository = $employeeRepository;
    }

    /**
     * Override FormTrait method to filter dictionary data to specific entity type
     */
    protected function getDictionary(): void
    {
        $this->traitGetDictionary();

        $this->dictionaries['EMPLOYEE_TYPE'] = $this->getDictionariesFields(
            config('ehealth.legal_entity_type.' . auth()->user()->legalEntity->type .'.roles'),
            'EMPLOYEE_TYPE'
        );

        // Employee can have only those positions which are allowed for his type/role
        foreach ($this->dictionaries['EMPLOYEE_TYPE'] as $employeeType => $description) {
            $keys = config("ehealth.employee_type.{$employeeType}.position", []);
            $this->employeeTypePosition[$employeeType] = $this->getDictionariesFields($keys, 'POSITION');
        }
    }

    public function setCertificateAuthority(): array|null
    {
        return $this->getCertificateAuthority = $this->getCertificateAuthority();
    }

    protected function getEmployee(): void
    {
        if ($this->employeeId) {
//            $employee = EmployeeModel::with('party', 'documents', 'educations')->findOrFail($this->employeeId);
            $this->employee = EmployeeModel::with('party', 'educations')->findOrFail($this->employeeId);

            // Створюємо $employeeData для `fill`
            $employeeData = [
                'party' => [
                    'lastName' => $this->employee->party->last_name,
                    'firstName' => $this->employee->party->first_name,
                    'secondName' => $this->employee->party->second_name,
                    'email' => $this->employee->party->email,
                    'gender' => $this->employee->party->gender,
                    'birthDate' => $this->employee->party->birth_date,
                    'taxId' => $this->employee->party->tax_id,
                    'employeeType' => $this->employee->employee_type,
                    'position' => $this->employee->position,
                    'phones' => $this->employee->party->phones ?? [],
                    'startDate' => optional($this->employee)->start_date,
            ],
//                'documents' => $employee->documents->toArray(),
                'educations' => $this->employee->educations->toArray(),
                'specialities' => $this->employee->specialities->toArray(),
                'scienceDegrees' => $this->employee->scienceDegrees->toArray(),
                'qualifications' => $this->employee->qualifications->toArray(),
            ];

            // Заповнюємо форму
            $this->employeeRequest->fill($employeeData);
        }
    }
}
