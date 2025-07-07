<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Forms\EmployeeForm;
use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\LegalEntity;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;

class AddPosition extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public EmployeeForm $form;
    public string $pageTitle;

    /**
     * CORRECTED AND FINAL: This logic is based on your suggestion.
     * It uses the same robust data-loading logic as the Edit component,
     * then applies the specific action for this page.
     */
    public function mount(LegalEntity $legalEntity, int $id, string $type = 'employee'): void
    {
        $this->getDictionary();

        // Step 1: Find the source model (Employee or Request) exactly like in EmployeeEdit.
        $source = match ($type) {
            'request' => EmployeeRequest::with(['revision', 'party'])->find($id),
            default => Employee::find($id),
        };

        if (!$source) {
            throw new ModelNotFoundException('Source model not found.');
        }

        // This handles cases where we might have an EmployeeRequest without a direct party link
        if (!$source->party && $source->employee_id) {
            $source->load('employee.party');
            $source->setRelation('party', $source->employee->party);
        }

        // Step 2: Use the central hydrate() method to populate the form with all data.
        // This will correctly fill name, email, phones, documents, etc.
        $this->form->hydrate($source);

        // Step 3: After the form is fully populated, clear only the position-related fields.
        $this->form->clearPositionFields();

        $this->pageTitle = __('forms.addPosition');
    }

    public function render(): View
    {
        return view('livewire.employee.employee', [
            'pageTitle' => $this->pageTitle,
        ]);
    }
}
