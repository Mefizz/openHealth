<?php

declare(strict_types=1);

namespace App\Livewire\Employee;

use AllowDynamicProperties;
use App\Core\Arr;
use App\Models\LegalEntity;
use App\Models\Relations\Party;
use App\Repositories\Repository;
use Illuminate\View\View;
use Livewire\Attributes\Locked;

#[AllowDynamicProperties]
class PartyEdit extends AbstractEmployeeFormManager
{
    #[Locked]
    public ?int $partyId = null;

    public function mount(LegalEntity $legalEntity, Party $party): void
    {
        $this->loadDictionaries();
        $this->loadDivisions($legalEntity);

        $this->party = $party;
        $this->partyId = $party->id;

        $employee = $party->employees()->latest('start_date')->first();

        if ($employee) {
            $this->form->hydrate($employee);
        } else {
            $this->form->hydrate($party);
        }
        $this->isPositionDataLocked = true;
    }

    public function boot(): void
    {
        if ($this->partyId) {
            $this->party = Party::findOrFail($this->partyId);
        }
    }

    /**
     * Similar to employee edit, but focus on Party data,
     */
    protected function handleDraftPersistence(): \App\Models\Employee\EmployeeRequest
    {
        $employee = $this->party->employees()->latest('start_date')->firstOrFail();

        $preparedData = $this->form->getPreparedData();
        $employeeRequestData = Arr::only($preparedData, ['position', 'start_date', 'end_date', 'employee_type', 'division_id', 'email']);

        $employeeRequestData['user_id'] = $this->party->user_id;
        $employeeRequestData['party_id'] = $this->party->id;

        $newRequest = Repository::employee()->createEmployeeRequestDraft($employeeRequestData, legalEntity());

        $nestedDataForRevision = $this->mapRevisionData($preparedData);
        $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);

        return $newRequest;
    }

    public function render(): View
    {
        return view('livewire.employee.employee-edit');
    }
}
