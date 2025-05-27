<?php

namespace App\Repositories;

use App\Models\Employee\EmployeeRequest;

class EmployeeRequestRepository
{
    public function create(array $data): EmployeeRequest
    {
        return EmployeeRequest::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $request = EmployeeRequest::findOrFail($id); // Або find()
        return $request->update($data);
    }

    public function updateById(int $id, array $data): bool
    {
        $request = EmployeeRequest::find($id);
        if ($request) {
            return $request->update($data);
        }
        return false;
    }

    public function find(int $id): ?EmployeeRequest
    {
        return EmployeeRequest::find($id);
    }
}

