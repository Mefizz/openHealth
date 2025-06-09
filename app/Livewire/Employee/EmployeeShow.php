<?php

namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use Illuminate\View\View;

/**
 * This component is responsible for SHOWING a single employee's data.
 */
class EmployeeShow extends EmployeeComponent
{
    /**
     * This public property holds the employee model.
     * It will be automatically injected by Livewire's Route Model Binding.
     */
    public Employee $employee;

    /**
     * The page title.
     */
    public string $pageTitle;

    /**
     * Mount the component.
     * Thanks to Route Model Binding, Laravel automatically finds the Employee
     * model from the route parameter and injects it here.
     *
     * @param Employee $employee The automatically resolved Employee model.
     */
    public function mount(Employee $employee): void
    {
        $this->employee = $employee->load(
            [
                'party.phones',
                'party.documents',
                'educations',
                'specialities',
                'qualifications',
                'scienceDegrees',
            ]
        );

        $this->getDictionary();
        $this->form->populateFromModel($this->employee);
        $this->pageTitle = __('forms.viewEmployee');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('livewire.employee.employee-show')->title($this->pageTitle);
    }
}
