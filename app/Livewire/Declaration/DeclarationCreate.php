<?php

declare(strict_types=1);

namespace App\Livewire\Declaration;

use App\Models\LegalEntity;
use Livewire\Attributes\Locked;

class DeclarationCreate extends DeclarationComponent
{
    #[Locked]
    public int $patientId;

    public function mount(LegalEntity $legalEntity, int $id): void
    {
        $this->patientId = $id;
    }

    public array $dictionaryNames = [];
}
