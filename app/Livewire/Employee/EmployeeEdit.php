<?php

namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeFormManager
{
    /**
     * Mount the component and populate the form with existing employee data.
     *
     * @param int $id The employee's primary ID from the route.
     * @return void
     */
    public function mount(int $id): void
    {
        // We call getDictionary() directly instead of parent::mount()
        $this->getDictionary();

        // The rest of your logic is perfectly correct
        $employee = Employee::findOrFail($id);
        $this->employee = $employee;
        $this->form->populateFromModel($this->employee);
    }

    /**
     * Render the component view.
     *
     * @return View
     */
    public function render(): View
    {
        return view('livewire.employee.employee-edit', [
            'pageTitle' => __('forms.edit_employee'),
        ]);
    }
}
