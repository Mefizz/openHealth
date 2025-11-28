<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use App\Classes\eHealth\EHealth;
use App\Enums\Status;
use App\Livewire\Contract\Forms\CapitationContractRequestForm as Form;
use App\Models\LegalEntity;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CapitationContractCreate extends ContractComponent
{
    public Form $form;

    public array $legalEntities;

    /**
     * List of related divisions
     *
     * @var array
     */
    public array $divisions;

    protected array $dictionaryNames = [
        'CONTRACT_TYPE',
        'CAPITATION_CONTRACT_CONSENT_TEXT',
        'MEDICAL_SERVICE',
    ];

    public function mount(LegalEntity $legalEntity): void
    {
        $this->baseMount($legalEntity);

        $this->legalEntities = LegalEntity::get(['id', 'edr'])->toArray();

        $this->divisions = $legalEntity->divisions->where('status', Status::ACTIVE)->toArray();
    }

    public function createLocally(): void
    {
        $this->form->validate();

        dd($this->form);
    }

    public function show()
    {
        $this->showSignatureModal = true;
    }

    public function sign(): void
    {
        // array:3 [▼ // app/Livewire/Contract/CapitationContractCreate.php:51
        //  "additional_document_url" => "https://storage-preprod-01.ehealth.gov.ua/contract-requests/4b/7e/41/ac/b9/4b0b9001-7ecd-41a0-ac0d-b9030fce6fcb/media/upload_contract_request_additional_documen ▶"
        //  "id" => "4b0b9001-7ecd-41a0-ac0d-b9030fce6fcb"
        //  "statute_url" => "https://storage-preprod-01.ehealth.gov.ua/contract-requests/4b/7e/41/ac/b9/4b0b9001-7ecd-41a0-ac0d-b9030fce6fcb/media/upload_contract_request_statute.pdf?X-Amz- ▶"
        //];

        try {
            $validated = $this->form->validate();
            $validatedCipher = $this->form->validate($this->form->rulesForSigning());
        } catch (ValidationException $exception) {
            Session::flash('error', $exception->validator->errors()->first());
            $this->setErrorBag($exception->validator->getMessageBag());

            return;
        }
//        dd($this->form->formatForApi($validated), $validatedCipher);

        $signedContent = signatureService()->signData(
            $this->form->formatForApi($validated),
            $validatedCipher['password'],
            $validatedCipher['knedp'],
            $validatedCipher['keyContainerUpload'],
            Auth::user()->party->taxId
        );

        $response = EHealth::contractRequest()->create(
            '4b0b9001-7ecd-41a0-ac0d-b9030fce6fcb',
            'capitation',
            ['signed_content' => $signedContent, 'signed_content_encoding' => 'base64']
        );
        dd($response->getData());
    }

    public function render(): View
    {
        return view('livewire.contract.capitation-contract-create');
    }
}
