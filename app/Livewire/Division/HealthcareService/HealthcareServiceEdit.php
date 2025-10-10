<?php

declare(strict_types=1);

namespace App\Livewire\Division\HealthcareService;

use App\Models\Division;
use App\Models\HealthcareService;
use App\Models\LegalEntity;
use Illuminate\View\View;

class HealthcareServiceEdit extends HealthcareServiceComponent
{
    public function mount(LegalEntity $legalEntity, Division $division, HealthcareService $healthcareService): void
    {
        $this->baseMount($legalEntity, $division);

        $this->form->fill($healthcareService);
    }

    public function render(): View
    {
        return view('livewire.division.healthcare-service.healthcare-service-edit');
    }
}
