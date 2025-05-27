<?php

namespace App\Services\EmployeeRequest;

use App\Enums\Status;
use App\Models\Employee\EmployeeRequest;
use App\Models\Relations\Party;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class EmployeeRequestBuilder
{
    private ?Party $party = null;
    private ?User $user = null;
    private array $data;

    public function build(): EmployeeRequest
    {
        $this->createParty($this->data['party'])
            ->createPhones($this->data['party']['phones'] ?? [])
            ->createPerson($this->data['party'])
            ->createUser()
            ->createDocuments($this->data['documents'] ?? [])
            ->createEducations($this->data['educations'] ?? [])
            ->createQualifications($this->data['qualifications'] ?? [])
            ->createSpecialities($this->data['specialities'] ?? [])
            ->createScienceDegrees($this->data['science_degrees'] ?? []);

        return $this->createEmployeeRequest();
    }

    public function setData(array $data): self
    {
        $this->data = $data;
        return $this;
    }

    public function createParty(array $partyData): self
    {
        $this->party = Party::create($partyData);
        return $this;
    }

    public function createPhones(array $phones): self
    {
        $this->party->phones()->createMany($phones);
        return $this;
    }

    public function createPerson(array $personData): self
    {
        $this->party->person()->create($personData);
        return $this;
    }

    public function createUser(): self
    {
        $this->user = User::create([
            'person_id' => $this->party->person->id,
            'email' => $this->party->person->email ?? Str::uuid().'@example.com',
            'password' => Hash::make(Str::random(12)),
        ]);
        return $this;
    }

    public function createDocuments(array $documents): self
    {
        $this->party->documents()->createMany($documents);
        return $this;
    }

    public function createEducations(array $educations): self
    {
        $this->party->educations()->createMany($educations);
        return $this;
    }

    public function createQualifications(array $qualifications): self
    {
        $this->party->qualifications()->createMany($qualifications);
        return $this;
    }

    public function createSpecialities(array $specialities): self
    {
        $this->party->specialities()->createMany($specialities);
        return $this;
    }

    public function createScienceDegrees(array $scienceDegrees): self
    {
        $this->party->scienceDegrees()->createMany($scienceDegrees);
        return $this;
    }

    private function createEmployeeRequest(): EmployeeRequest
    {
        return EmployeeRequest::create([
            'party_id' => $this->party->id,
            'user_id' => $this->user->id,
            'status' => $this->data['status'] ?? Status::NEW,
            'employee_type' => $this->data['employee_type'] ?? null,
            'position' => $this->data['position'] ?? null,
            'start_date' => $this->data['start_date'] ?? now()->toDateString(),
        ]);
    }
}
