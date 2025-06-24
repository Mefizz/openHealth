<?php
namespace App\Livewire\Employee;

use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

class EmployeeShow extends EmployeeComponent
{
    public Employee|EmployeeRequest $employee;
    public string $pageTitle;
    public bool $isReadOnly = true;

    public function mount(LegalEntity $legalEntity, int $id): void
    {
        $this->loadDictionaries();

        $routeName = request()->route()->getName();

        $source = match (true) {
            str_starts_with($routeName, 'employee-request.') => EmployeeRequest::findOrFail($id),
            str_starts_with($routeName, 'employee.') => Employee::findOrFail($id),
            default => throw new ModelNotFoundException('Unsupported route for showing.'),
        };

        $this->employee = $source;
        $this->form->hydrate($source);
        $this->pageTitle = __('forms.viewEmployee');
    }

    public function render(): View
    {
        return view('livewire.employee.employee-show', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee,
            'isReadOnly' => $this->isReadOnly,
        ]);
    }
}
