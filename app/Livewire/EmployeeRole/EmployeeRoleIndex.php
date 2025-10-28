<?php

declare(strict_types=1);

namespace App\Livewire\EmployeeRole;

use Illuminate\View\View;
use Livewire\Component;

class EmployeeRoleIndex extends Component
{
    public function render(): View
    {
        return view('livewire.employee-role.employee-role-index');
    }
}
