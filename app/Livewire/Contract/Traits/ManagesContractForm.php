<?php

namespace App\Livewire\Contract\Traits;

use App\Classes\Cipher\Api\CipherApi;
use App\Livewire\Contract\Forms\Api\ContractRequestApi;
use App\Livewire\Contract\Forms\ContractFormRequest;
use App\Livewire\LegalEntity\Forms\LegalEntitiesRequestApi;
use App\Models\Contract;
use App\Services\SignatureService;
use App\Traits\FormTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;

trait ManagesContractForm
{
    use FormTrait, WithFileUploads;

    public ContractFormRequest $form;
    public array $external_contractors = [];
    public string $external_contractor_key = '';

    public function sendApiRequest()
    {
        try {
            $this->form->rulesForModelValidate();
            $this->form->validate($this->form->rulesForKepOnly());

            $payload = $this->buildPayload();
            $payload = removeEmptyKeys($payload);

            $taxId = $this->legalEntity->employees()->where('employee_type', 'OWNER')->first()?->party['tax_id'] ?? '';
            if (empty($taxId)) {
                $this->dispatch('flashMessage', ['message' => 'Не вдалося знайти ІПН власника.', 'type' => 'error']);
                return;
            }

            $signatureService = resolve(SignatureService::class);
            $base64Data = $signatureService->signData(
                $payload,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                CipherApi::SIGNATORY_INITIATOR_BUSINESS,
                $taxId
            );

            $data = [
                'signed_content'          => $base64Data,
                'signed_content_encoding' => 'base64',
            ];

            $contractId = Cache::get($this->contractCacheKey);
            $contract_response = ContractRequestApi::contractRequestApi($data, $contractId);

            $contract = new Contract($contract_response);
            $contract->uuid = $contract_response['id'];
            $contract->contractor_legal_entity_id = $contract_response['contractor_legal_entity']['id'];
            $contract->contractor_owner_id = $contract_response['contractor_owner']['id'];
            $this->legalEntity->contracts()->save($contract);

            Cache::forget($this->contractCacheKey);
            session()->flash('success', 'Контракт успішно створено!');
            return redirect()->route('contract.index', [$this->legalEntity]);

        } catch (ValidationException $e) {
            throw $e;
        }
    }

    private function buildPayload(): array
    {
        $data = $this->form->toArray();

        if (!empty($this->external_contractors)) {
            $data['external_contractors'] = array_map(function ($contractor) {
                unset($contractor['name'], $contractor['edrpou']);
                if (isset($contractor['divisions'])) {
                    $contractor['divisions'] = [$contractor['divisions']];
                }
                return $contractor;
            }, $this->external_contractors);
        }

        unset($data['knedp'], $data['keyContainerUpload'], $data['password']);

        $data['additional_document_md5'] = md5_file($this->form->additional_document_md5->getRealPath());
        $data['statute_md5'] = md5_file($this->form->statute_md5->getRealPath());
        $data['end_date'] = Carbon::parse($this->form->end_date)->format('Y-m-d');
        $data['start_date'] = Carbon::parse($this->form->start_date)->format('Y-m-d');
        $data['contractor_owner_id'] = $this->legalEntity->getOwner()->uuid;
        $data['consent_text'] = $this->dictionaries['CAPITATION_CONTRACT_CONSENT_TEXT']['APPROVED'];
        $data['contractor_payment_details']['payer_account'] = str_replace(' ', '', $data['contractor_payment_details']['payer_account']);

        return $data;
    }

    public function addExternalContractors(): void
    {
        $validatedData = $this->validate(
            [
                'form.external_contractors.edrpou' => 'required|string|digits:8',
            ]
        );
        $edrpou = $validatedData['form']['external_contractors']['edrpou'];

        $foundEntity = LegalEntitiesRequestApi::getLegalEntities($edrpou);

        if (empty($foundEntity['data'])) {
            $this->addError('form.external_contractors.edrpou', 'Організацію з таким ЄДРПОУ не знайдено.');
            return;
        }

        $this->form->external_contractors['legal_entity_id'] = $foundEntity['data'][0]['id'];

        $this->form->rulesForModelValidate('external_contractors');

        if ($this->external_contractor_key !== '') {
            $this->external_contractors[$this->external_contractor_key] = $this->form->external_contractors;
        } else {
            $this->external_contractors[] = $this->form->external_contractors;
        }

        $this->resetExternalContractorForm();
        $this->closeModal();
    }

    public function editExternalContractors($key): void
    {
        $this->external_contractor_key = $key;
        $this->form->external_contractors = $this->external_contractors[$key];
        $this->openModal('addExternalContractors');
    }

    public function deleteExternalContractors($key): void
    {
        unset($this->external_contractors[$key]);
    }

    protected function resetExternalContractorForm(): void
    {
        $this->external_contractor_key = '';
        $this->form->external_contractors = [];
    }

    public function openModalSigned(): void
    {
        $this->form->rulesForModelValidate();
        $this->openModal('signed_content');
    }

    public function initializeCacheKey(): void
    {
        if (isset($this->legalEntity)) {
            $this->contractCacheKey = 'register_contract_form-' . $this->legalEntity->uuid;
        }
    }
}
