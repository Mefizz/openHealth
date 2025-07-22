<?php

namespace App\Policies;

use App\Models\Employee\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee:read', 'ehealth');
    }

    public function view(User $user, Employee $employee): bool
    {
        if ((int)$employee->legal_entity_id !== (int)legalEntity()->id) {
            return false;
        }
        return $user->hasPermissionTo('employee:details', 'ehealth');
    }

    public function update(User $user, Employee $employee): bool
    {
        if ((int)$employee->legal_entity_id !== (int)legalEntity()->id) {
            return false;
        }
        return $user->hasPermissionTo('employee:write', 'ehealth');
    }

    public function dismiss(User $user, Employee $employee): bool
    {
        if ((int)$employee->legal_entity_id !== (int)legalEntity()->id) {
            return false;
        }
        return $user->hasPermissionTo('employee:deactivate', 'ehealth');
    }
}
