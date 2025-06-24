<?php

namespace App\Livewire\Employee;

use App\Livewire\Employee\Traits\ManagesEmployeeForm;
use App\Models\LegalEntity;
use Illuminate\View\View;

class EmployeeCreate extends EmployeeComponent
{
    use ManagesEmployeeForm;

    public string $pageTitle;

    /**
     * The mount method now only contains logic specific to the "Create" action.
     * The getDictionary() method is called automatically from the parent's boot() method.
     */
    public function mount(LegalEntity $legalEntity): void
    {
        $this->loadDictionaries();
        $this->pageTitle = __('forms.addEmployee');

        $this->isPersonalDataLocked = false;
    }

    public function render(): View
    {
        return view('livewire.employee.employee-create', [
            'pageTitle' => $this->pageTitle,
        ]);
    }
}
