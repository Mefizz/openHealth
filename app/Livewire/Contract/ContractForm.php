<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Classes\Cipher\Api\CipherApi;
use App\Classes\eHealth\EHealth;
use App\Livewire\Contract\Forms\BaseContractFormRequest;
use App\Livewire\LegalEntity\Forms\LegalEntitiesRequestApi;
use App\Models\Contract;
use App\Models\Division;
use App\Models\LegalEntity;
use App\Classes\Cipher\Traits\Cipher;
use App\Traits\FormTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Database\Eloquent\Collection;
use Livewire\Component;
use Livewire\WithFileUploads;

class ContractForm extends Component
{
    use FormTrait;
    use WithFileUploads;
    use Cipher;

    public const string CACHE_PREFIX = 'register_contract_form';

    public ?array $dictionaryNames = [
        'CONTRACT_TYPE',
        'CAPITATION_CONTRACT_CONSENT_TEXT',
        'SPECIALITY_TYPE'
    ];

    public LegalEntity $legalEntity;

    public ?Collection $divisions;
    public ?Collection $healthcareServices;

    public BaseContractFormRequest $contract_request;

    public array $legalEntityApi = [];

    public array $external_contractors = [];
    public string $external_contractor_key = '';
    public string $legalEntity_search = '';
    public string $contractCacheKey;

    public string $legalEntitySearch = '';

    public function boot(): void
    {
        $this->contractCacheKey = self::CACHE_PREFIX . '-' . legalEntity()->uuid;
    }

    public function mount(LegalEntity $legalEntity, $id = ''): void
    {
        if ($id !== '') {
            $this->contract_request->previous_request_id = $id;
        }
        $this->getDictionary();
        $this->getLegalEntity();
    }

    public function getLegalEntity(): void
    {
        $this->legalEntity = legalEntity();

        $this->divisions = $this->legalEntity->getActiveDivisions();
    }

    public function contractType()
    {
        return $this->legalEntity->contract_type;
    }

    public function getLegalEntityApi(): void
    {
        if (strlen($this->contract_request->external_contractors['name']) >= 3) {
            $this->legalEntityApi = LegalEntitiesRequestApi::getLegalEntities($this->contract_request->external_contractors['name']);
        }

    }

    public function addExternalContractors(): void
    {
        // 1. Manually validate the EDRPOU first
        $this->validate([
                            'contract_request.external_contractors.edrpou' => 'required|string|digits:8'
                        ]);

        // 2. Find the legal entity by EDRPOU
        $edrpou = $this->contract_request->external_contractors['edrpou'];
        $foundEntity = LegalEntitiesRequestApi::getLegalEntities($edrpou);

        // 3. CORRECTED CHECK: Check if the 'data' array inside the response is empty
        if (empty($foundEntity['data'])) {
            $this->addError('contract_request.external_contractors.edrpou', 'Організацію з таким ЄДРПОУ не знайдено.');

            return;
        }

        // 4. CORRECTED ACCESS: Get the ID from the first element of the 'data' array
        $this->contract_request->external_contractors['legal_entity_id'] = $foundEntity['data'][0]['id'];

        // 5. Now, validate the rest of the fields
        $this->validateExternalContractors();

        // 6. The rest of the original method logic
        if ($this->external_contractor_key !== '') {
            $this->external_contractors[$this->external_contractor_key] = $this->contract_request->external_contractors;
        } else {
            $this->external_contractors[] = $this->contract_request->external_contractors;
        }

        // Clear the form for the next entry
        $this->contract_request->external_contractors = [];
        $this->closeModal();
    }

    private function validateExternalContractors(): void
    {
        $this->contract_request->rulesForModelValidate('external_contractors');
    }

    private function resetExternalContractorKeyAndRequest(): void
    {
        $this->external_contractor_key = '';
        $this->contract_request->external_contractors = [];
    }

    public function closeModal(): void
    {
        $this->resetExternalContractorKeyAndRequest();
        $this->contract_request->external_contractors = [];
        $this->legalEntity_search = '';
        $this->showModal = false;
    }

    public function editExternalContractors($key): void
    {
        $this->external_contractor_key = $key;
        $this->contract_request->external_contractors = $this->external_contractors[$this->external_contractor_key];
        $this->openModal('addExternalContractors');
    }

    public function openModalSigned(): void
    {
        $this->contract_request->rulesForModelValidate();
        $this->openModal('signed_content');
    }

    public function deleteExternalContractors($key): void
    {
        unset($this->external_contractors[$key]);
    }

    public function getHealthcareServices($id): object|array
    {
        if (!$id) {
            return [];
        }
        $division = Division::where('uuid', $id)->first();
        if (!$division) {
            return [];
        }
        $this->contract_request->external_contractors['divisions']['id'] = $division->uuid;

        return $this->healthcareServices = $division
            ->healthcareService()
            ->get();

    }

    /**
     * Try to get tax ID from legal entity owner depends on authorized user
     *
     * @return string
     */
    protected function getTaxIdFromOwner(): string
    {
        return $this->legalEntity->employees()->where('employee_type', 'OWNER')->first()->party['tax_id'] ?? '';
    }

    public function sendApiRequest(): \Illuminate\Http\RedirectResponse
    {
        $this->contract_request->rulesForModelValidate();
        $removeKeyEmpty = removeEmptyKeys($this->requestBuilder());
        $taxId = $this->getTaxIdFromOwner();

        $base64Data = new CipherApi()->sendSession(
            json_encode($removeKeyEmpty, JSON_THROW_ON_ERROR),
            $this->password,
            $this->keyContainerUpload,
            $this->knedp,
            CipherApi::SIGNATORY_INITIATOR_BUSINESS,
            $taxId
        );
        if (isset($base64Data['errors'])) {
            $this->dispatch('flashMessage', [
                'message' => $base64Data['errors'],
                'type' => 'error'
            ]);

            return;
        }

        $data = [
            'signed_content' => $base64Data,
            'signed_content_encoding' => 'base64',
        ];

        //todo add request
        $contract_response = EHealth::contract();
        $contract = new Contract($contract_response);
        $contract->uuid = $contract_response['id'];
        $contract->contractor_legal_entity_id = $contract_response['contractor_legal_entity']['id'];
        $contract->contractor_owner_id = $contract_response['contractor_owner']['id'];
        $this->legalEntity->contracts()->save($contract);
        Cache::forget($this->contractCacheKey);

        return redirect()->route('contract.index', [legalEntity()]);
    }

    public function requestBuilder(): array
    {
        $data = $this->contract_request->toArray();

        if (!empty($this->external_contractors)) {
            $data['external_contractors'] = array_map(function ($contractor) {
                unset($contractor['name']);
                if (isset($contractor['divisions'])) {
                    $contractor['divisions'] = [$contractor['divisions']];
                }

                return $contractor;
            }, $this->external_contractors);

        }

        $data['additional_document_md5'] = md5_file($this->contract_request->additional_document_md5->getRealPath());
        $data['statute_md5'] = md5_file($this->contract_request->statute_md5->getRealPath());
        $data['end_date'] = Carbon::parse($this->contract_request->end_date)->format('Y-m-d');
        $data['start_date'] = Carbon::parse($this->contract_request->start_date)->format('Y-m-d');
        $data['contractor_owner_id'] = $this->legalEntity->getOwner()->uuid;
        $data['consent_text'] = $this->dictionaries['CAPITATION_CONTRACT_CONSENT_TEXT']['APPROVED'];
        $data['contractor_payment_details'] = $this->contract_request->contractor_payment_details;
        $data['contractor_payment_details']['payer_account'] = str_replace(' ', '', $data['contractor_payment_details']['payer_account']);

        return $data;
    }

    public function render()
    {
        return view('livewire.contract.contract-form');
    }

}
