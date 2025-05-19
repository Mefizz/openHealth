<?php

namespace App\Livewire\Employee;

use App\Models\Employee\Employee as EmployeeModel;

class EmployeeEdit extends EmployeeComponent
{
    protected ?EmployeeModel $employee;

    public function mount(?int $id = null): void
    {
        $this->employeeId = $id; // <-- треба передати в базовий клас
        parent::mount(); // <-- ініціалізуємо форму
        $this->employee = EmployeeModel::findOrFail($id);
        $this->getEmployeeForm();
    }

    protected function getEmployeeForm(): void
    {
        parent::getEmployee(); // Call the parent method from EmployeeComponent to retrieve basic employee data

        dd($this->form->party);


    }


    public function render()
    {
        $pageTitle = __('forms.edit_employee') . ' : ' . __($this->employee->getFullNameAttribute());

        return view('livewire.employee.employee-edit', compact('pageTitle'));
    }
}
