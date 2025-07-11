<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\Employee;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;
    public bool $isPersonalDataLocked = true;

    /**
     * This mount method is now simple: it only accepts an Employee.
     */
    public function mount(LegalEntity $legalEntity, Employee $employee): void
    {
        $this->loadDictionaries();

        $this->employee = $employee;
        $this->form->hydrate($this->employee);
        $this->pageTitle = __('forms.editEmployee');
    }

    /**
     * This component also uses the shared form template.
     */
    public function render(): View
    {
        return view('livewire.employee.employee');
    }
}
