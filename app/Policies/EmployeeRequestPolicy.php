<?php

namespace App\Policies;

use App\Models\Employee\EmployeeRequest;
use App\Models\User;

class EmployeeRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('employee_request:read', 'ehealth');
    }

    public function view(User $user, EmployeeRequest $employeeRequest): bool
    {
        // Для запитів використовуємо той самий дозвіл, що і для списку
        return $user->hasPermissionTo('employee_request:read', 'ehealth');
    }

    public function create(User $user): bool
    {
        // Для створення, редагування та видалення використовуємо один дозвіл
        return $user->hasPermissionTo('employee_request:write', 'ehealth');
    }

    public function update(User $user, EmployeeRequest $employeeRequest): bool
    {
        if (!is_null($employeeRequest->uuid)) return false;
        return $user->hasPermissionTo('employee_request:write', 'ehealth');
    }

    public function delete(User $user, EmployeeRequest $employeeRequest): bool
    {
        if (!is_null($employeeRequest->uuid)) return false;
        return $user->hasPermissionTo('employee_request:write', 'ehealth');
    }
}
