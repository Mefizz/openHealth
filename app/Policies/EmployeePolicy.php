<?php
namespace App\Policies;
use App\Models\Employee\Employee;
use App\Models\User;

class EmployeePolicy
{
    public function viewAny(User $user): bool
    {
        // Per docs, ADMIN, HR, DOCTOR can read the list
        return $user->hasPermissionTo('employee:read', 'ehealth');
    }

    public function view(User $user, Employee $employee): bool
    {
        // Per docs, only HR has 'employee:details' permission
        return $user->hasPermissionTo('employee:details', 'ehealth');
    }

    public function update(User $user, Employee $employee): bool
    {
        // Per docs, only HR has 'employee:write' permission
        return $user->hasPermissionTo('employee:write', 'ehealth');
    }

    public function dismiss(User $user, Employee $employee): bool
    {
        // Per docs, only HR has 'employee:deactivate' permission
        return $user->hasPermissionTo('employee:deactivate', 'ehealth');
    }
}
