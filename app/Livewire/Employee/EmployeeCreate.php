<?php

namespace App\Livewire\Employee;

use Illuminate\View\View;

class EmployeeCreate extends EmployeeFormManager
{
    /**
     * Mount the component and initialize necessary data.
     */
    public function mount(): void
    {
        // Now we explicitly call the dictionary loading logic here.
        $this->getDictionary();
    }

    public function render(): View
    {
        return view('livewire.employee.employee-create', [
            'pageTitle' => __('forms.add_employee'),
        ]);
    }
}
