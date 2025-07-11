<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeRequestEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;
    public bool $isPersonalDataLocked = true;

    /**
     * This mount method is simple and clear: it only accepts an EmployeeRequest.
     * Route Model Binding will provide the model automatically.
     */
    public function mount(LegalEntity $legalEntity, EmployeeRequest $employee_request): void
    {
        $this->loadDictionaries();

        $this->employeeRequest = $employee_request;
        $this->form->hydrate($this->employeeRequest);
        $this->pageTitle = __('forms.edit_employee_request');
    }

    /**
     * This component uses the same shared form template.
     */
    public function render(): View
    {
        return view('livewire.employee.employee');
    }
}
