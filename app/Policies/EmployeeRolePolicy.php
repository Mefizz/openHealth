<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class EmployeeRolePolicy
{
    /**
     * User allow to add an employee role
     */
    public function create(User $user): Response
    {
        if ($user->cannot('employee_role:write')) {
            return Response::denyWithStatus(404);
        }

        // Can be created for legal entities with the following statuses.
        if (!in_array(legalEntity()->status, ['ACTIVE', 'SUSPENDED'], true)) {
            return Response::denyWithStatus(404);
        }

        return Response::allow();
    }
}
