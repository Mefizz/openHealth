<?php

declare(strict_types=1);

namespace App\Livewire\License;

use App\Models\LegalEntity;
use App\Models\License;
use Illuminate\View\View;

class LicenseView extends LicenseEdit
{
    protected License $license;

    public function mount(LegalEntity $legalEntity, License $license): void
    {
        $this->license = $license;
    }

    public function render(): View
    {
        return view('livewire.license.license-view')->with([
            'license' => $this->license,
        ]);
    }
}
