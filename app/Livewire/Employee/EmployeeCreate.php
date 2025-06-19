<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;

class EmployeeCreate extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;

    public function mount(): void
    {
        $this->getDictionary();
        $this->pageTitle = __('forms.addEmployee');
    }

    public function render()
    {
        return view('livewire.employee.employee-create')->title($this->pageTitle);
    }
}
