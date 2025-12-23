<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\Status;
use App\Models\Employee\Employee;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeePolicy
{
    public function viewAny(User $user): Response
    {
        return $user->can('employee:read')
            ? Response::allow()
            : Response::deny(__('employees.policy.view_any_denied'));
    }

    public function view(User $user, Employee $employee): Response
    {
        if ((int) $employee->legalEntityId !== (int) legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        return $user->can('employee:details')
            ? Response::allow()
            : Response::deny(__('employees.policy.view_denied'));
    }

    public function update(User $user, Employee $employee): Response
    {
        // 1. Status check
        if ($employee->status === Status::DISMISSED) {
            return Response::deny(__('employees.policy.emp.dismissed_no_edit'));
        }

        // 2. VERIFICATION: Whether the employee is related to the user
        if (is_null($employee->user_id)) {
            return Response::deny(__('employees.policy.no_user_linked'));
        }

        // 3. Verification of affiliation with the institution (already exists)
        if ((int) $employee->legalEntityId !== (int) legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        // 4. Checking user access rights
        return $user->can('employee:write')
            ? Response::allow()
            : Response::deny(__('employees.policy.emp.update_denied'));
    }

    public function deactivate(User $user, Employee $employee): Response
    {
        if ((int) $employee->legalEntityId !== (int) legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        return $user->can('employee:deactivate')
            ? Response::allow()
            : Response::deny(__('employees.policy.deactivate_denied'));
    }

    /**
     * Determine whether the user can sync the employee with eHealth.
     */
    public function sync(User $user, Employee $employee): Response
    {
        // 1. Verification of affiliation with the current institution
        if ((int) $employee->legalEntityId !== (int) legalEntity()->id) {
            return Response::denyWithStatus(404);
        }

        // 2. State Check
        if (!$employee->userId || !$employee->partyId || !$employee->uuid) {
            return Response::deny(__('employees.policy.sync_missing_data'));
        }

        // 3. PERMISSIONS
        return $user->can('employee:write')
            ? Response::allow()
            : Response::deny(__('employees.policy.emp.update_denied'));
    }
}
