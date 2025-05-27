<?php

namespace App\Actions\EmployeeRequest;

use App\Models\Employee\EmployeeRequest;
use App\Services\EmployeeRequest\EmployeeRequestBuilder;
use Illuminate\Support\Facades\DB;

class CreateEmployeeRequestAction
{
    public function execute(array $data): EmployeeRequest
    {
        return DB::transaction(function () use ($data) {
            return (new EmployeeRequestBuilder($data))->build();
        });
    }
}
