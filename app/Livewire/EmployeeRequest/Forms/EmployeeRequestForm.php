<?php

namespace App\Livewire\EmployeeRequest\Forms;

use App\Core\Arr;
use App\Models\Employee\EmployeeRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Livewire\Form;
use Illuminate\Validation\Rule;
use App\Rules\Email;
use App\Rules\TaxId;
use App\Rules\PhoneNumber;
use App\Rules\DocumentNumber;
use App\Rules\BirthDate;
use App\Rules\Cyrillic;

class EmployeeRequestForm extends Form
{
    public array $party = [
        'firstName' => '',
        'lastName' => '',
        'secondName' => '',
        'gender' => '',
        'birthDate' => '',
        'phones' => [['type' => '', 'number' => '']],
        'email' => '',
        'taxId' => '',
        'employeeType' => '',
        'position' => '',
        'startDate' => '',
    ];

    public array $documents = [];
    public array $educations = [];
    public array $specialities = [];
    public array $scienceDegrees = [];
    public array $qualifications = [];

    public string $status = 'NEW'; // Це, ймовірно, статус EmployeeRequest в БД

    // Поля для динамічного відображення (якщо це потрібно для форми)
    public array $employeeTypePosition = []; // Ці дані повинні приходити з EmployeeRequestCreate

    public function setEmployeeTypePosition(array $data): void
    {
        $this->employeeTypePosition = $data;
    }

    public function rules(): array
    {
        return [
            'party.firstName' => ['required', 'string', new Cyrillic],
            'party.lastName' => ['required', 'string', new Cyrillic],
            'party.secondName' => ['nullable', 'string', new Cyrillic],
            'party.birthDate' => ['required', 'date', new BirthDate],
            'party.gender' => ['required', 'string', Rule::in(['MALE', 'FEMALE', 'OTHER'])],
            'party.phones' => ['required', 'array', 'min:1'],
            'party.phones.*.type' => ['required', 'string'],
            'party.phones.*.number' => ['required', 'string', new PhoneNumber],
            'party.email' => ['nullable', 'string', 'email', new Email],
            'party.taxId' => ['nullable', 'string', new TaxId],
            'party.employeeType' => ['required', 'string', Rule::in(array_keys($this->employeeTypePosition))],
            'party.position' => ['required', 'string'], // Rule::in(array_keys($this->employeeTypePosition[$this->party['employeeType']]))
            'party.startDate' => ['required', 'date'],

            // Documents
            'documents' => ['nullable', 'array'],
            'documents.*.type' => ['required', 'string'],
            'documents.*.number' => ['required', 'string', new DocumentNumber],
            'documents.*.issuedBy' => ['nullable', 'string'],
            'documents.*.issuedAt' => ['nullable', 'date'],

            // Educations
            'educations' => ['nullable', 'array'],
            'educations.*.country' => ['required', 'string'],
            'educations.*.city' => ['required', 'string'],
            'educations.*.institution_name' => ['required', 'string'],
            'educations.*.speciality' => ['required', 'string'],
            'educations.*.degree' => ['required', 'string'],
            'educations.*.issued_date' => ['nullable', 'date'],
            'educations.*.diploma_number' => ['nullable', 'string'],

            // Specialities
            'specialities' => ['nullable', 'array'],
            'specialities.*.speciality_type' => ['required', 'string'],
            'specialities.*.speciality_level' => ['required', 'string'],
            'specialities.*.division_type' => ['required', 'string'],
            'specialities.*.assigned_at' => ['required', 'date'],
            'specialities.*.assigned_by' => ['required', 'string'],

            // Science Degrees
            'scienceDegrees' => ['nullable', 'array'],
            'scienceDegrees.*.degree' => ['required', 'string'],
            'scienceDegrees.*.coutry' => ['required', 'string'], // Typo: 'coutry' instead of 'country'
            'scienceDegrees.*.city' => ['required', 'string'],
            'scienceDegrees.*.issued_date' => ['required', 'date'],
            'scienceDegrees.*.institution_name' => ['required', 'string'],
            'scienceDegrees.*.speciality' => ['required', 'string'],
            'scienceDegrees.*.diploma_number' => ['nullable', 'string'],

            // Qualifications
            'qualifications' => ['nullable', 'array'],
            'qualifications.*.type' => ['required', 'string'],
            'qualifications.*.country' => ['required', 'string'],
            'qualifications.*.institution_name' => ['required', 'string'],
            'qualifications.*.issued_date' => ['nullable', 'date'],
            'qualifications.*.speciality' => ['required', 'string'],
            'qualifications.*.certificate_number' => ['nullable', 'string'],
        ];
    }

    public function afterValidation(): void
    {
        if (empty($this->party['taxId']) && empty($this->documents)) {
            throw ValidationException::withMessages([
                'party.taxId' => __('validation.employee.tax_id_or_document_required'),
                'documents' => __('validation.employee.tax_id_or_document_required'),
            ]);
        }

        if (count($this->party['phones']) > 0) {
            $primary = array_filter($this->party['phones'], fn($p) => $p['type'] === 'MOBILE' && !empty($p['number']));
            if (count($primary) === 0) {
                throw ValidationException::withMessages([
                    'party.phones.0.number' => __('validation.employee.mobile_phone_required'), // Або інше відповідне поле
                ]);
            }
        }
    }

    /**
     * Method for partial validation per group
     */
    public function rulesForGroup(string $group): array
    {
        return collect($this->rules())
            ->filter(fn($_, $key) => str_starts_with($key, $group . '.'))
            ->toArray();
    }

    /**
     * Transform camelCase to snake_case and return validated data
     * @throws ValidationException
     */
    public function validatedSnake(): array
    {
        return Arr::snakeKeys($this->validate());
    }

    // --- ПОТРІБНО ВИДАЛИТИ АБО РЕФАКТОРИТИ ЦІ МЕТОДИ З FORM OBJECT ---
    public function store()
    {
        $validated = Validator::make($this->toArray(), $this->rules())->validate();

        return EmployeeRequest::create([
            'data' => $validated,
        ]);
    }

    public function submit()
    {
        $this->validate([
            'form.fullName' => 'required|string', // This seems like an old validation for a different form structure
            'form.taxId' => 'required|string',
            'form.position' => 'required|string',
        ]);

        // This method calls a service directly, violating the Form Object's Single Responsibility
        \App\Services\EmployeeRequestSubmitService::submit($this->form);
    }
}
