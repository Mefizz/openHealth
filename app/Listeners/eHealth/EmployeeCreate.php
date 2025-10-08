<?php

declare(strict_types=1);

namespace App\Listeners\eHealth;

use App\Classes\eHealth\EHealth;
use App\Core\Arr;
use App\Enums\Employee\RequestStatus;
use App\Enums\Employee\RevisionStatus;
use App\Events\EHealthUserLogin;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use App\Models\User;
use App\Repositories\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Throwable;

class EmployeeCreate
{
    /**
     * @throws Throwable
     */
    public function handle(EHealthUserLogin $event): void
    {
        $user = $event->user;

        $signedRequests = $this->getSignedEmployeeRequests($user);
        if ($signedRequests->isEmpty()) {
            return;
        }

        $eHealthEmployees = $this->getApprovedEHealthEmployees($event, $signedRequests);
        if (empty($eHealthEmployees)) {
            return;
        }

        $newEHealthEmployees = $this->filterOutExistingEmployees($user, $eHealthEmployees);
        if (empty($newEHealthEmployees)) {
            return;
        }

        $newRoles = $this->processNewEmployees(
            $user,
            $newEHealthEmployees,
            $signedRequests,
            $event->legalEntity
        );

        $this->syncUserRoles($user, $newRoles, $event->legalEntity);
    }

    /**
     * Fetches signed employee requests for a given user.
     */
    private function getSignedEmployeeRequests(User $user): Collection
    {
        return EmployeeRequest::with('revision')
            ->where('status', RequestStatus::SIGNED)
            ->where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Fetches approved employees from eHealth based on the user's tax ID.
     */
    private function getApprovedEHealthEmployees(EHealthUserLogin $event, Collection $signedRequests): array
    {
        $taxId = $signedRequests->first()->revision->data['party']['tax_id'];

        return EHealth::employee()->getMany([
                                                'legal_entity_id' => $event->legalEntity->uuid,
                                                'tax_id' => $taxId,
                                                'status' => 'APPROVED',
                                            ])->validate();
    }

    /**
     * Filters out employees that are already present in the local database.
     */
    private function filterOutExistingEmployees(User $user, array $eHealthEmployees): array
    {
        $existingUuids = $user->employees()->pluck('uuid')->all();

        return array_filter($eHealthEmployees, fn (array $employee) => !in_array($employee['uuid'], $existingUuids));
    }

    /**
     * Processes new employees within a database transaction.
     * Creates local Employee and Party records and collects new roles.
     * @return string[]
     * @throws Throwable
     */
    private function processNewEmployees(User $user, array $newEHealthEmployees, Collection $signedRequests, LegalEntity $legalEntity): array
    {
        $newRoles = [];

        DB::transaction(function () use ($user, $newEHealthEmployees, $signedRequests, $legalEntity, &$newRoles) {
            foreach ($newEHealthEmployees as $eHealthEmployee) {
                $employeeRequest = $this->findMatchingLocalRequest($signedRequests, $eHealthEmployee);

                if (!$employeeRequest) {
                    continue;
                }

                $newEmployee = $this->createOrUpdateEmployee($user, $eHealthEmployee, $employeeRequest, $legalEntity);
                $this->updateAssociatedRecords($employeeRequest, $newEmployee, $user);

                if (!$user->hasRole($newEmployee->employeeType)) {
                    $newRoles[] = $newEmployee->employeeType;
                }
            }
        });

        return $newRoles;
    }

    /**
     * Creates or updates a local Employee and associated Party record.
     *
     * @throws Throwable
     */
    private function createOrUpdateEmployee(User $user, array $eHealthEmployee, EmployeeRequest $request, LegalEntity $legalEntity): Employee
    {
        $dataFromRevision = EHealth::employeeRequest()->mapCreate($request->revision->data);
        $dataFromEHealth = Arr::only($eHealthEmployee, ['uuid', 'position', 'employee_type', 'start_date', 'end_date']);

        $employee = Employee::updateOrCreate(
            ['uuid' => $dataFromEHealth['uuid']],
            array_merge($dataFromRevision['employee'], $dataFromEHealth, [
                'legal_entity_id' => $legalEntity->id,
                'legal_entity_uuid' => $legalEntity->uuid,
                'user_id' => $user->id
            ])
        );

        return Repository::employee()->updateDetails(
            $employee,
            array_merge($dataFromRevision['party'], $eHealthEmployee['party'], ['user_id' => $user->id]),
            $dataFromRevision['documents'],
            $dataFromRevision['phones'],
            $dataFromRevision['educations'] ?? null,
            $dataFromRevision['specialities'] ?? null,
            $dataFromRevision['qualifications'] ?? null,
            $dataFromRevision['scienceDegree'] ?? null
        );
    }

    /**
     * Updates the original EmployeeRequest and its Revision to mark them as processed.
     */
    private function updateAssociatedRecords(EmployeeRequest $request, Employee $employee, User $user): void
    {
        $request->update([
                             'employee_id' => $employee->id,
                             'status' => RequestStatus::APPROVED,
                             'applied_at' => now(),
                             'user_id' => $user->id,
                             'party_id' => $employee->partyId
                         ]);

        $request->revision->update(['status' => RevisionStatus::APPLIED]);
    }

    /**
     * This matching logic is fragile as it relies on text fields.
     * A more robust solution would be to use a unique token exchanged during the signing process.
     * This implementation is kept for now but should be considered for a future upgrade.
     */
    private function findMatchingLocalRequest(Collection $employeeRequests, array $employee): ?EmployeeRequest
    {
        return $employeeRequests->where('position', $employee['position'])
            ->where('employee_type', $employee['employee_type'])
            ->first(function (EmployeeRequest $employeeRequest) use ($employee) {
                $party = $employeeRequest->revision->data['party'];

                return $party['first_name'] === $employee['party']['first_name']
                    && $party['last_name'] === $employee['party']['last_name']
                    && $party['second_name'] === $employee['party']['second_name'];
            });
    }

    /**
     * Assigns new roles to the user if any were collected.
     */
    private function syncUserRoles(User $user, array $newRoles, LegalEntity $legalEntity): void
    {
        if (empty($newRoles)) {
            return;
        }

        setPermissionsTeamId($legalEntity->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $user->assignRole($newRoles);
    }
}
