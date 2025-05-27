<?php

namespace App\Services;

use App\Models\Employee\EmployeeRequest;

class EmployeeRequestService
{
    public function createFromForm(array $data): EmployeeRequest
    {
        return EmployeeRequest::create($data);
    }
}
