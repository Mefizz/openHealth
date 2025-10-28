<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\EmployeeRole;
use Throwable;

class EmployeeRoleRepository
{
    /**
     * Store data after successful creating in EHealth.
     *
     * @param  array  $data
     * @return EmployeeRole
     * @throws Throwable
     */
    public function store(array $data): EmployeeRole
    {
        return EmployeeRole::create($data);
    }
}
