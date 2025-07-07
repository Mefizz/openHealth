<?php

namespace App\Livewire\Employee\Forms;

use App\Core\Arr;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Relations\Party;
use App\Rules\BirthDate;
use App\Rules\Name;
use App\Rules\PhoneNumber;
use App\Rules\UniqueEmailInLegalEntity;
use Illuminate\Database\Eloquent\Model;
use Livewire\Form;

class EmployeeForm extends Form
{
    public string $position = '';
    public string $employeeType = '';
    public string $startDate = '';
    public ?string $endDate = null;
    public ?int $existingPartyId = null;
    public ?string $divisionId = null;
    public array $documents = [];
    public array $party = [
        'lastName' => '', 'firstName' => '', 'secondName' => '', 'gender' => '',
        'birthDate' => '', 'phones' => [['type' => 'MOBILE', 'number' => '']],
        'taxId' => '', 'noTaxId' => false, 'email' => '',
        'workingExperience' => null, 'aboutMyself' => '',
    ];
    public array $doctor = [
        'specialities' => [], 'scienceDegrees' => [], 'qualifications' => [], 'educations' => [],
    ];
    public ?string $knedp = null;
    public $keyContainerUpload;
    public ?string $password = null;

    public function rulesForSave(): array
    {
        return array_merge(
            $this->rootFieldsRules(),
            $this->partyRules(),
            $this->documentsRules(),
            $this->doctorRules()
        );
    }

    public function rulesForKepOnly(): array
    {
        return [
            'knedp' => ['required', 'string'],
            'password' => ['required', 'string'],
            'keyContainerUpload' => ['required', 'file', 'extensions:dat,pfx,pk8,zs2,jks,p7s'],
        ];
    }

    protected function rootFieldsRules(): array
    {
        return [
            'position' => ['required', 'string'],
            'employeeType' => ['required', 'string'],
            'startDate' => ['required', 'date'],
            'endDate' => ['nullable', 'date'],
            'divisionId' => ['nullable', 'string'],
        ];
    }

    protected function partyRules(): array
    {
        return [
            'party.lastName' => ['required', new Name()],
            'party.firstName' => ['required', new Name()],
            'party.secondName' => ['nullable', new Name()],
            'party.gender' => ['required', 'string'],
            'party.birthDate' => ['required', 'date', new BirthDate()],
            'party.phones' => ['required', 'array', 'min:1'],
            'party.phones.*.number' => ['required', new PhoneNumber()],
            'party.phones.*.type' => ['required', 'string'],
            'party.taxId' => ['required_if:party.noTaxId,false', 'string'],
            'party.noTaxId' => ['boolean'],
            'party.email' => ['nullable', 'email', new UniqueEmailInLegalEntity($this->existingPartyId)],
            'party.workingExperience' => ['required', 'numeric', 'min:1'],
            'party.aboutMyself' => ['nullable', 'string'],
        ];
    }

    protected function documentsRules(): array
    {
        return [
            'documents' => ['required', 'array', 'min:1'],
            'documents.*.type' => ['required', 'string'],
            'documents.*.number' => ['required', 'string'],
            'documents.*.issuedBy' => ['required', 'string', 'min:1'],
            'documents.*.issuedAt' => ['required', 'date_format:Y-m-d'],
        ];
    }

    /**
     * Defines validation rules for doctor-related data (now nested under 'doctor').
     * Updated scienceDegrees and qualifications to be nullable.
     *
     * @return array
     */
    protected function doctorRules(): array
    {
        $doctorTypes = config('ehealth.doctors_type');
        $isDoctor = in_array($this->employeeType, $doctorTypes, true);

        $educationRules = ['nullable', 'array'];
        $specialitiesRules = ['nullable', 'array'];

        if ($isDoctor) {
            $educationRules[] = 'required';
            $educationRules[] = 'min:1';
            $specialitiesRules[] = 'required';
            $specialitiesRules[] = 'min:1';
        }

        $scienceDegreesRules = ['nullable', 'array'];
        $qualificationsRules = ['nullable', 'array'];

        return [
            'doctor.educations' => $educationRules,
            'doctor.educations.*.country' => ['required', 'string', 'max:255'],
            'doctor.educations.*.city' => ['required', 'string', 'max:255'],
            'doctor.educations.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.educations.*.issuedDate' => ['nullable', 'date'],
            'doctor.educations.*.diplomaNumber' => ['required', 'string', 'max:255'],
            'doctor.educations.*.degree' => ['required', 'string', 'max:255'],
            'doctor.educations.*.speciality' => ['required', 'string', 'max:255'],

            'doctor.specialities' => $specialitiesRules,
            'doctor.specialities.*.speciality' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.specialityOfficio' => ['required', 'boolean'],
            'doctor.specialities.*.level' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.qualificationType' => ['required', 'string'],
            'doctor.specialities.*.attestationName' => ['required', 'string', 'max:255'],
            'doctor.specialities.*.attestationDate' => ['required', 'date'],
            'doctor.specialities.*.validToDate' => ['nullable', 'date'],
            'doctor.specialities.*.certificateNumber' => ['required', 'string', 'max:255'],

            'doctor.scienceDegrees' => $scienceDegreesRules,
            'doctor.scienceDegrees.*.country' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.city' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.degree' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.diplomaNumber' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.speciality' => ['required', 'string', 'max:255'],
            'doctor.scienceDegrees.*.issuedDate' => ['nullable', 'date'],

            'doctor.qualifications' => $qualificationsRules,
            'doctor.qualifications.*.type' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.institutionName' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.speciality' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.issuedDate' => ['required', 'date'],
            'doctor.qualifications.*.certificateNumber' => ['required', 'string', 'max:255'],
            'doctor.qualifications.*.validTo' => ['nullable', 'date', 'after_or_equal:doctor.qualifications.*.issuedDate'],
            'doctor.qualifications.*.additionalInfo' => ['nullable', 'string'],
        ];
    }

    /**
     * The single "smart" method to populate the form from any data source.
     */
    public function hydrate(Model|Party|null $source = null): void
    {
        $this->reset();

        if ($source === null) {
            return;
        }

        match (get_class($source)) {
            Employee::class => $this->hydrateFromEmployee($source),
            EmployeeRequest::class => $this->hydrateFromEmployeeRequest($source),
            Party::class => $this->hydrateFromParty($source),
        };
    }

    /**
     * Resets only the fields related to a specific position/employment.
     */
    public function clearPositionFields(): void
    {
        $this->reset('position', 'employeeType', 'startDate', 'endDate', 'divisionId');
    }

    private function hydrateFromEmployee(Employee $employee): void
    {
        $employee->loadMissing(['party.phones', 'party.documents', 'educations', 'specialities']);
        if ($employee->party) {
            $this->populatePartyData($employee->party);
        }
        $this->position = $employee->position;
        $this->employeeType = $employee->employee_type;
        $this->startDate = $employee->start_date?->format('Y-m-d');
        $this->endDate = $employee->end_date?->format('Y-m-d');
        $this->divisionId = $employee->division_id;

        $this->doctor['educations'] = $employee->educations->map(fn($edu) => Arr::toCamelCase($edu->toArray()))->toArray();
        $this->doctor['specialities'] = $employee->specialities->map(fn($spec) => Arr::toCamelCase($spec->toArray()))->toArray();
    }

    private function hydrateFromEmployeeRequest(EmployeeRequest $request): void
    {
        $request->loadMissing(['party', 'revision']);
        $revisionData = $request->revision->data ?? [];
        $unpacked = $this->unpackRevisionData($revisionData);

        // Populate form with revision data as the base
        $this->position = $unpacked['employeeData']['position'] ?? '';
        $this->employeeType = $unpacked['employeeData']['employee_type'] ?? '';
        $this->startDate = $unpacked['employeeData']['start_date'] ?? '';
        $this->documents = Arr::toCamelCase($unpacked['documentsData']);
        $this->doctor = Arr::toCamelCase($unpacked['doctorData']);
        $this->party = array_merge($this->party, Arr::toCamelCase($unpacked['partyData']));
        $this->party['phones'] = !empty($unpacked['phonesData']) ? Arr::toCamelCase($unpacked['phonesData']) : [['type' => 'MOBILE', 'number' => '']];

        // Now, overwrite with live data from Party to ensure it's current
        if ($request->party) {
            $this->party['lastName'] = $request->party->last_name;
            $this->party['firstName'] = $request->party->first_name;
            $this->party['secondName'] = $request->party->second_name;
            $this->party['email'] = $request->party->email;
            $this->existingPartyId = $request->party->id;
        }
    }

    /**
     * Hydrates the form from a Party model.
     * Use case: "Add Position" for an existing person.
     * This method now contains the full "smart" logic.
     */
    private function hydrateFromParty(Party $party): void
    {
        // 1. First, populate with live data from the Party and its direct relations.
        // This will correctly fill in name, email, and will try to fill phones/documents.
        $this->populatePartyData($party);

        // 2. Fallback logic: If documents or phones are still empty after the first step,
        //    it means the Party might only have EmployeeRequests. Let's try to get
        //    the data from the latest request's revision.
        $needsRevisionCheck = empty($this->documents) || empty($this->party['phones'][0]['number']);

        if ($needsRevisionCheck) {
            $latestRequest = $party->employeeRequests()->with('revision')->latest()->first();

            if ($latestRequest && $latestRequest->revision) {
                $unpacked = $this->unpackRevisionData($latestRequest->revision->data ?? []);

                // If phones are still empty, get them from the revision.
                if (empty($this->party['phones'][0]['number']) && !empty($unpacked['phonesData'])) {
                    $this->party['phones'] = Arr::toCamelCase($unpacked['phonesData']);
                }

                // If documents are still empty, get them from the revision.
                if (empty($this->documents) && !empty($unpacked['documentsData'])) {
                    $this->documents = Arr::toCamelCase($unpacked['documentsData']);
                }
            }
        }
    }

    private function populatePartyData(Party $party): void
    {
        $party->loadMissing(['phones', 'documents']);
        $this->existingPartyId = $party->id;
        $this->party['lastName'] = $party->last_name;
        $this->party['firstName'] = $party->first_name;
        $this->party['secondName'] = $party->second_name;
        $this->party['gender'] = $party->gender;
        $this->party['birthDate'] = $party->birth_date?->format('Y-m-d');
        $this->party['taxId'] = $party->tax_id;
        $this->party['noTaxId'] = (bool)$party->no_tax_id;
        $this->party['email'] = $party->email;
        $this->party['workingExperience'] = $party->working_experience;
        $this->party['aboutMyself'] = $party->about_myself;

        $phones = $party->phones;
        $this->party['phones'] = ($phones && $phones->isNotEmpty()) ? $phones->map(fn($p) => ['type' => $p->type, 'number' => $p->number])->toArray() : [['type' => 'MOBILE', 'number' => '']];
        $documents = $party->documents;
        $this->documents = ($documents && $documents->isNotEmpty()) ? $documents->map(fn($d) => Arr::toCamelCase($d->only(['type', 'number', 'issued_by', 'issued_at'])))->toArray() : [];
    }

    protected function unpackRevisionData(array $revisionData): array
    {
        return [
            'employeeData' => $revisionData['employee_request_data'] ?? [], 'partyData' => $revisionData['party'] ?? [],
            'documentsData' => $revisionData['documents'] ?? [], 'phonesData' => $revisionData['phones'] ?? [],
            'doctorData' => $revisionData['doctor'] ?? [],
        ];
    }

    /**
     * Prepares and returns a FLAT array of all form data for the repository.
     * The logic for creating a nested structure for the revision is moved to the Trait.
     */
    public function getPreparedData(): array
    {
        $formData = $this->all();
        $partyData = $formData['party'] ?? [];
        unset($formData['party']);
        $formData = array_merge($formData, $partyData);

        unset(
            $formData['existingPartyId'],
            $formData['knedp'],
            $formData['keyContainerUpload'],
            $formData['password']
        );

        return Arr::toSnakeCase($formData);
    }

    /**
     * Resets the form to its default state.
     */
    public function reset(...$properties): void
    {
        parent::reset(...$properties);

        $this->position = '';
        $this->employeeType = '';
        $this->startDate = '';
        $this->endDate = null;
        $this->existingPartyId = null;

        $this->knedp = null;
        $this->keyContainerUpload = null;
        $this->password = null;

        $this->documents = [];
        $this->party = [
            'lastName' => '', 'firstName' => '', 'secondName' => '', 'gender' => '',
            'birthDate' => '', 'phones' => [['type' => '', 'number' => '']],
            'taxId' => '', 'noTaxId' => false, 'email' => '',
            'workingExperience' => null, 'aboutMyself' => '',
        ];
        $this->doctor    = [
            'scienceDegrees' => [], 'qualifications' => [],
        ];
    }
}
