<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;

    /**
     * Mount the component and populate the form with existing employee data.
     * The parameter name now matches the route parameter name '{employeeId}'.
     *
     * @param int $employeeId The employee's primary ID from the route.
     */
    public function mount(int $employeeId): void
    {
        $this->getDictionary();
        $this->employeeId = $employeeId;
        $this->loadEmployee();
        $this->pageTitle = __('forms.editEmployee');
    }

    /**
     * Render the component view.
     */
    public function render(): View
    {
        return view('livewire.employee.employee-edit', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
        ]);
    }
}
