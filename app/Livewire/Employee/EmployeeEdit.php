<?php

namespace App\Livewire\Employee;

use App\Models\LegalEntity;
use App\Models\Employee\Employee as Employee;

class EmployeeEdit extends EmployeeComponent
{
    protected Employee $employee;

    public function mount(LegalEntity $legalEntity, int $id = null): void
    {
        $this->employee = Employee::findOrFail($id);

        parent::mount($legalEntity);
    }

    public function render()
    {
        return view('livewire.employee.employee-edit');
    }
}
