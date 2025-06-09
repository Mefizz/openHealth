<?php

namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use Illuminate\View\View;

class EmployeeShow extends EmployeeComponent
{
    public Employee $employee;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee->load([
                                              'party.phones',
                                              'party.documents',
                                              'educations',
                                              'specialities',
                                              'qualifications',
                                              'scienceDegrees',
                                              'division'
                                          ]);

        $this->getDictionary();
        $this->form->populateFromModel($this->employee);
    }

    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'pageTitle' => __('forms.view_employee'),
        ]);
    }
}
