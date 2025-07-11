<?php
namespace App\Livewire\Employee;

use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeRequestShow extends EmployeeComponent
{
    public EmployeeRequest $position;
    public string $pageTitle;
    public bool $isReadOnly = true;

    public function mount(LegalEntity $legalEntity, EmployeeRequest $employee_request): void
    {
        $this->loadDictionaries();

        $this->position = $employee_request;
        $this->form->hydrate($this->position);
        $this->pageTitle = __('forms.view_employee_request');
    }

    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'position' => $this->position
        ]);
    }
}
