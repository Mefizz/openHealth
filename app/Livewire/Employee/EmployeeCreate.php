<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use Illuminate\View\View;

// This component now handles both creating and editing employees.
class EmployeeCreate extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;

    /**
     * Mounts the component. It handles both the create and edit scenarios
     * based on the presence of the employeeId.
     */
    public function mount(?int $employeeId = null): void
    {
        $this->getDictionary();

        if ($employeeId) {
            $this->pageTitle = __('forms.editEmployee');
            $this->employeeId = $employeeId;
            $this->loadEmployee();
        } else {
            $this->pageTitle = __('forms.addEmployee');
        }
    }

    /**
     * Renders the component view.
     */
    public function render(): View
    {
        return view('livewire.employee.employee-create', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
        ]);
    }
}
