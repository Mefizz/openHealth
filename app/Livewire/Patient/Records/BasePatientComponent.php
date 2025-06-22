<?php

declare(strict_types=1);

namespace App\Livewire\Patient\Records;

use App\Models\LegalEntity;
use App\Models\Person\Person;
use Livewire\Attributes\Locked;
use Livewire\Component;

abstract class BasePatientComponent extends Component
{
    #[Locked]
    public string $patientId;

    public string $firstName;

    public string $lastName;

    public ?string $secondName = null;

    public string $verificationStatus;

    protected string $uuid;

    public function boot(): void
    {
        if ($this->patientId) {
            $this->loadPatientData();
        }
    }

    public function mount(LegalEntity $legalEntity, string $patientId): void
    {
        $this->patientId = $patientId;
        $this->initializeComponent();
    }

    /**
     * Get all needed data from DB about patient.
     *
     * @return void
     */
    protected function loadPatientData(): void
    {
        $patient = Person::select(['uuid', 'first_name', 'last_name', 'second_name', 'verification_status'])
            ->where('id', $this->patientId)
            ->first()
            ?->toArray();

        $this->firstName = $patient['first_name'];
        $this->lastName = $patient['last_name'];
        $this->secondName = $patient['second_name'] ?? null;
        $this->verificationStatus = $patient['verification_status'];
        $this->uuid = $patient['uuid'];
    }

    /**
     * A method that can be overridden in child classes for additional initialization.
     *
     * @return void
     */
    protected function initializeComponent(): void
    {
    }
}
