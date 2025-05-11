<?php

namespace App\Livewire\Employee\Forms;

use App\Rules\BirthDate;
use App\Rules\Name;
use App\Traits\HandlesSnakeCaseForm;
use Illuminate\Validation\ValidationException;
use Livewire\Form;

use function Livewire\of;

class EmployeeForm extends Form
{
    use HandlesSnakeCaseForm;

    public string $status = 'NEW';

    /**
     * Default values are transferred to the Alpine on the frontend
     */
    public ?array $party = [
        'position' => '',
        'employeeType' => '',
        'startDate' => '',
        'phones' => [
            [
                'type' => '',
                'number' => '',
            ]
        ]
    ];

    public function mount()
    {
        $this->party['startDate'] = now()->toDateString();
    }

    public array $documents = [];
    public ?array $education = [
        'country' => '',
    ];
    public ?array $educations = [];
    public ?array $speciality = [];
    public ?array $specialities = [];
    public ?array $scienceDegree = [];
    public ?array $qualification = [];
    public ?array $qualifications = [];

    protected function rules(): array
    {
        return [
            // Основне
            'division_id' => 'required|uuid',
            'legal_entity_id' => 'required|uuid',
            'position' => 'required|string',
            'start_date' => 'required|date',
            'end_date' => 'nullable|date',
            'status' => 'required|in:NEW,REJECTED,APPROVED',
            'employee_type' => 'required|string',

            // Party
            'party.first_name' => ['required', new Name()],
            'party.last_name' => ['required', new Name()],
            'party.second_name' => [new Name()],
            'party.birth_date' => ['required', 'date', new BirthDate()],
            'party.gender' => ['required', 'in:MALE,FEMALE'],
            'party.no_tax_id' => 'required|boolean',
            'party.tax_id' => 'required_if:party.no_tax_id,false|string|min:8|max:10',
            'party.email' => 'required|email',
            'party.phones' => 'required|array|min:1',
            'party.phones.*.type' => 'required|string|in:MOBILE,LAND_LINE', // уточни типи
            'party.phones.*.number' => 'required|string|regex:/^\+380\d{9}$/',
            'party.documents' => 'required|array|min:1',
            'party.documents.*.type' => 'required|string|min:3',
            'party.documents.*.number' => 'required|string|min:3',
            'party.documents.*.issued_by' => 'required|string|min:3',
            'party.documents.*.issued_at' => 'required|date',
            'party.working_experience' => 'nullable|numeric|min:0',
            'party.about_myself' => 'nullable|string|min:3',

            // Doctor — education
            'doctor.educations' => 'required|array|min:1',
            'doctor.educations.*.country' => 'required|string|size:2',
            'doctor.educations.*.city' => 'required|string|min:2',
            'doctor.educations.*.institution_name' => 'required|string|min:3',
            'doctor.educations.*.issued_date' => 'required|date',
            'doctor.educations.*.diploma_number' => 'required|string|min:3',
            'doctor.educations.*.degree' => 'required|string|min:3',
            'doctor.educations.*.speciality' => 'required|string|min:3',

            // Doctor — qualifications
            'doctor.qualifications' => 'nullable|array',
            'doctor.qualifications.*.type' => 'required|string|min:3',
            'doctor.qualifications.*.institution_name' => 'required|string|min:3',
            'doctor.qualifications.*.speciality' => 'required|string|min:3',
            'doctor.qualifications.*.issued_date' => 'required|date',
            'doctor.qualifications.*.certificate_number' => 'required|string|min:3',
            'doctor.qualifications.*.valid_to' => 'required|date',
            'doctor.qualifications.*.additional_info' => 'nullable|string',

            // Doctor — specialities
            'doctor.specialities' => 'required|array|min:1',
            'doctor.specialities.*.speciality' => 'required|string|min:3',
            'doctor.specialities.*.speciality_officio' => 'required|boolean',
            'doctor.specialities.*.level' => 'required|string|min:3',
            'doctor.specialities.*.qualification_type' => 'required|string|min:3',
            'doctor.specialities.*.attestation_name' => 'required|string|min:3',
            'doctor.specialities.*.attestation_date' => 'required|date',
            'doctor.specialities.*.valid_to_date' => 'required|date',
            'doctor.specialities.*.certificate_number' => 'required|string|min:3',

            // Doctor — science degree
            'doctor.science_degree.country' => 'required|string|size:2',
            'doctor.science_degree.city' => 'required|string|min:2',
            'doctor.science_degree.degree' => 'required|string|min:2',
            'doctor.science_degree.institution_name' => 'required|string|min:3',
            'doctor.science_degree.diploma_number' => 'required|string|min:3',
            'doctor.science_degree.speciality' => 'required|string|min:3',
            'doctor.science_degree.issued_date' => 'required|date',
        ];
    }


    /**
     * @throws ValidationException
     */
    public function rulesForModelValidate(string $model): array
    {
        return $this->validate($this->rulesForModel($model)->toArray());
    }

    public function validateBeforeSendApi(): array
    {

        $doctorTypes = config('ehealth.doctors_type'); // Get doctor types from config

        // Check if documents is empty
        if (empty($this->documents)) {
            return [
                'error'   => true,
                'message' => __('validation.custom.documentsEmpty'),
            ];
        }

        // Check if taxId is empty
        if (isset($this->party['taxId']) && empty($this->party['taxId'])) {
            return [
                'error'   => true,
                'message' => __('validation.custom.documentsEmpty'),
            ];
        }
        // Check if doctor type is empty
        if ( in_array($this->party['employeeType'],$doctorTypes) && empty($this->specialities)) {
            return [
                'error'   => true,
                'message' => __('validation.custom.specialityTable'),
            ];
        }
        // Check if doctor type is empty
        if ( in_array($this->party['employeeType'],$doctorTypes) && empty($this->educations)) {
            return [
                'error'   => true,
                'message' => __('validation.custom.educationTable'),
            ];
        }

        return [
            'error'   => false,
            'message' => '',
        ];
    }

    public function validated(): array
    {
        return $this->validate($this->rules());
    }

}
