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
use App\Repositories\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EmployeeCreate
{
    /**
     * Is applied only for users/employees added through the MIS
     * Create a new employee and party (not exists) during first login. The data is retrieved from the revisions table
     * First performing a lookup in the employee_requests table if the user has any pending requests with a status of SIGNED.
     * If such a request exists, get associated revision from the revisions table
     * use the data from the revision to make request to the E-Health API to get the list of associated employees
     *
     */
    public function handle(EHealthUserLogin $event): void
    {
        $user = $event->user;

        /**
         * Get associated email from the revision table. Comparing email during registration with email from the E-Health user details API response
         * is the only reliable way to match the user to their newly created employee record(s) in E-Health.
         */
        $employeeRequests = EmployeeRequest::with('revision')
            ->where('status', RequestStatus::SIGNED)
            ->where('email', $user->email)
            ->orderBy('created_at', 'desc')
            ->get();

        if ($employeeRequests->isEmpty()) {
            return;
        }

        $taxId = $employeeRequests->first()->revision->data['party']['tax_id'];

        $employees = EHealth::employee()->getMany([
            'legal_entity_id' => $event->legalEntity->uuid,
            'tax_id' => $taxId,
            'status' => 'APPROVED',
        ])->validate();

        if (empty($employees)) {
            return;
        }

        $existingUuids = $user->employees()->pluck('uuid')->all();

        // Filter out employees that already exist in the local database
        $employees = array_filter($employees, fn(array $employee) => !in_array($employee['uuid'], $existingUuids));

        if (empty($employees)) {
            return;
        }

        DB::transaction(function () use ($user, $employees, $employeeRequests, $event, &$newRoles) {
            foreach ($employees as $employee) {

                // Find correspondent employee request
                $employeeRequest = $this->findEmployeeRequest($employeeRequests, $employee);

                /**
                 * Haven't found a matching request, skip this employee
                 * TODO We might try to create an employee with partial data from E-Health
                 */
                if (!$employeeRequest) {
                    continue;
                }

                $dataLocal = EHealth::employeeRequest()->mapCreate($employeeRequest->revision->data);
                $employeeEhealth = Arr::only($employee, ['uuid', 'position', 'employee_type', 'start_date', 'end_date']);
                $newEmployee = Employee::create(array_merge(
                    $dataLocal['employee'],
                    $employeeEhealth,
                    [
                        'legal_entity_id' => $event->legalEntity->id,
                        'legal_entity_uuid' => $event->legalEntity->uuid,
                        'user_id' => $user->id
                    ]
                ));

                $newEmployee = Repository::employee()->updateDetails(
                    $newEmployee,
                    array_merge($dataLocal['party'], $employee['party'], ['user_id' => $user->id]),
                    $dataLocal['documents'],
                    $dataLocal['phones'],
                    $dataLocal['educations'] ?? null,
                    $dataLocal['specialities'] ?? null,
                    $dataLocal['qualifications'] ?? null,
                    $dataLocal['scienceDegree'] ?? null
                );

                $employeeRequest->update(['employee_id' => $newEmployee->id, 'status' => RequestStatus::APPROVED, 'applied_at' => now(), 'user_id' => $user->id, 'party_id' => $newEmployee->partyId]);
                $employeeRequest->revision->update(['status' => RevisionStatus::APPLIED]);

                if (!$user->hasRole($newEmployee->employeeType)) {
                    $newRoles[] = $newEmployee->employeeType;
                }
            }
        });

        // Synchronize all new employees
        setPermissionsTeamId($event->legalEntity->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');
        $user->assignRole($newRoles);
    }

    /**
     * @param Collection<EmployeeRequest> $employeeRequests Collection of employee requests found by the current user email
     * @param array $employee The employee data from E-Health get employees API endpoint https://uaehealthapi.docs.apiary.io/#reference/public.-medical-service-provider-integration-layer/employees/get-employees-list?console=1
     * @return EmployeeRequest|null
     */
    protected function findEmployeeRequest(Collection $employeeRequests, array $employee): ?EmployeeRequest
    {
        return $employeeRequests->where('position', $employee['position'])
            ->where('employee_type', $employee['employee_type'])
            ->first(function (EmployeeRequest $employeeRequest) use ($employee) {
                $party = $employeeRequest->revision->data['party'];
                return $party['first_name'] == $employee['party']['first_name']
                    && $party['last_name'] == $employee['party']['last_name']
                    && $party['second_name'] == $employee['party']['second_name'];
            });
    }
}
