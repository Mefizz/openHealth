<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

class EmployeeEdit extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;

    /**
     * The mount method now contains only the logic specific to the "Edit" action.
     * It receives the crucial LegalEntity parameter.
     */
    public function mount(LegalEntity $legalEntity, int $id): void
    {
        $this->loadDictionaries();

        $routeName = request()->route()?->getName();

        $source = match (true) {
            str_starts_with($routeName, 'employee-request.') => EmployeeRequest::findOrFail($id),
            str_starts_with($routeName, 'employee.') => Employee::findOrFail($id),
            default => throw new ModelNotFoundException('Unsupported route for editing.'),
        };

        if ($source instanceof Employee) {
            $this->employee = $source;
        } else {
            $this->employeeRequest = $source;
        }

        $this->form->hydrate($source);
        $this->pageTitle = __('forms.editEmployee');
    }

    public function render(): View
    {
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
            'employee' => $this->employee ?? $this->employeeRequest,
        ]);
    }
}
