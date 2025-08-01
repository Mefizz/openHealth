<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Livewire\Contract\Traits\ManagesContractForm;
use App\Models\LegalEntity;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Component;

class ContractCreate extends Component
{
    use ManagesContractForm;

    public array $dictionaryNames = [
        'CONTRACT_TYPE',
        'CAPITATION_CONTRACT_CONSENT_TEXT',
        'SPECIALITY_TYPE'
    ];

    // 2. Оголошуємо публічну властивість для підрозділів
    public ?Collection $divisions = null;

    // Всі інші публічні властивості, необхідні для роботи
    public ?LegalEntity $legalEntity;
    public string|bool  $showModal = false;
    protected string    $contractCacheKey;

    public function mount(LegalEntity $legalEntity): void
    {
        $this->legalEntity = $legalEntity;

        $this->getDictionary();
        $this->initializeCacheKey();

        $this->divisions = $this->legalEntity->getActiveDivisions();
    }

    public function render(): View
    {
        return view('livewire.contract.contract-form');
    }
}
