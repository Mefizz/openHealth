<?php

namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
// We need to extend the base component that has dictionary logic
use App\Livewire\Employee\EmployeeComponent;

/**
 * This component is responsible for SHOWING a single employee's data.
 * It extends EmployeeComponent to inherit dictionary loading and the form object.
 */
class EmployeeShow extends EmployeeComponent
{
    /**
     * This public property holds the employee model.
     * Livewire will automatically make this available to the Blade view.
     * This line is required.
     */
    public Employee $employee;

    /**
     * Mount the component.
     *
     * @param int $id The employee's primary ID from the route.
     */
    public function mount(int $id): void
    {
        // 1. Find the employee and eager load all relations
        $this->employee = Employee::with([
                                             'party.phones',
                                             'party.documents',
                                             'educations',
                                             'specialities',
                                             'qualifications',
                                             'scienceDegrees'
                                         ])->findOrFail($id);

        // 2. Load dictionaries using the inherited method
        $this->getDictionary();

        // 3. Populate the form object, so the partials can display the data
        $this->form->populateFromModel($this->employee);
    }

    /**
     * Render the component view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        $pageTitle = __('Перегляд співробітника');

        return view('livewire.employee.employee-show', [
            'pageTitle' => $pageTitle
        ]);
    }
}
