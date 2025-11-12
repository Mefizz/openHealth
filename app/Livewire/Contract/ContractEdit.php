<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Livewire\Contract\Traits\ManagesContractForm;
use App\Models\Contract;
use App\Models\LegalEntity;
use Illuminate\Support\Collection;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class ContractEdit extends Component
{
    use ManagesContractForm;

    #[Locked]
    public ?string $contractUuid = null;

    public ?Contract $contract;
    public ?LegalEntity $legalEntity;
    public ?array       $dictionaries = [];
    public ?Collection  $divisions    = null;
    public string|bool $showModal = false;
    protected string   $contractCacheKey;

    public array $dictionaryNames = [
        'CONTRACT_TYPE',
        'CAPITATION_CONTRACT_CONSENT_TEXT',
        'SPECIALITY_TYPE'
    ];

    public function mount(LegalEntity $legalEntity, Contract $contract): void
    {
        $this->legalEntity = $legalEntity;
        $this->contract = $contract;
        $this->contractUuid = $contract->uuid;

        $this->getDictionary();
        $this->initializeCacheKey();
        $this->divisions = $this->legalEntity->getActiveDivisions();

        $contractData = $contract->toArray();

        unset($contractData['statute_md5'], $contractData['additional_document_md5']);


        $this->form->fill($contractData);

    }

    public function render(): View
    {
        return view('livewire.contract.contract-form');
    }
}
