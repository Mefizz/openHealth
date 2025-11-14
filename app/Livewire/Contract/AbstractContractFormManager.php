<?php

declare(strict_types=1);

namespace App\Livewire\Contract;

use AllowDynamicProperties;
use App\Classes\eHealth\EHealth;
use App\Exceptions\EHealth\EHealthResponseException;
use App\Exceptions\EHealth\EHealthValidationException;
use App\Livewire\Contract\Forms\CapitationContractFormRequest;
use App\Livewire\LegalEntity\Forms\LegalEntitiesRequestApi;
use App\Models\Contract;
use App\Models\Division;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\WithFileUploads;
use Throwable;

/**
 * (EN) Abstract manager for contract forms (create/edit).
 * Mimics the AbstractEmployeeFormManager pattern.
 * THIS IS THE "BRAIN" - it holds all shared logic.
 */
#[AllowDynamicProperties]
abstract class AbstractContractFormManager extends ContractComponent
{
    use WithFileUploads;

    public ?Collection $divisions = null;
    public ?Collection $healthcareServices = null;

    // === External Contractor Modal Logic ===
    public array $external_contractors_list = [];
    public array $external_contractor_modal_state = [];
    public string $external_contractor_key = '';
    // === End Modal Logic ===

    /**
     * Saves the current form state as a local draft.
     * Mimics the 'save' method in AbstractEmployeeFormManager.
     */
    public function save(): void
    {
        Log::info('Saving contract draft...', ['id' => $this->contractId]);
        try {
            // 0. Sync modal list to form object
            if ($this->form instanceof CapitationContractFormRequest) {
                $this->form->external_contractors = $this->external_contractors_list;
            }

            // 1. Validate the form data
            $this->form->validate();

            // 2. Delegate persistence to the concrete class (ContractCreate or ContractEdit)
            $contract = $this->handleDraftPersistence();
            $this->contractId = $contract->id;

            // This is new: redirect to 'edit' page after first save from 'create'
            if (request()->route()->named('contracts.create')) {
                $this->dispatch('flashMessage', ['message' => __('forms.contract_draft_created'), 'type' => 'success']);
                $this->redirect(route('contracts.edit', ['legalEntity' => $this->legalEntity->uuid, 'contract' => $contract->uuid]));
            } else {
                $this->dispatch('flashMessage', ['message' => __('forms.contract_draft_saved'), 'type' => 'success']);
            }

        } catch (Exception $e) {
            $this->handleGeneralException($e);
        }
    }

    /**
     * Validates, saves, and opens the signature modal.
     */
    public function prepareForSigning(): void
    {
        try {
            // 0. Sync modal list to form object
            if ($this->form instanceof CapitationContractFormRequest) {
                $this->form->external_contractors = $this->external_contractors_list;
            }

            $this->form->validate();
            $this->contract = $this->handleDraftPersistence(); // Save latest changes
            $this->contractId = $this->contract->id;

            $this->dispatch('flashMessage', ['message' => __('forms.contract_draft_saved'), 'type' => 'success']);
            $this->dispatch('open-signature-modal');
        } catch (Exception $e) {
            $this->handleGeneralException($e);
        }
    }

    /**
     * The main "sign and send to eHealth" action.
     * Mimics the 'sign' method in AbstractEmployeeFormManager.
     */
    public function sign()
    {
        Log::info('Attempting to sign contract.', ['id' => $this->contractId]);

        try {
            // 0. Sync modal list to form object
            if ($this->form instanceof CapitationContractFormRequest) {
                $this->form->external_contractors = $this->external_contractors_list;
            }

            // 1. Validate all form data + KEP fields
            $this->form->validate();
            $this->form->validate($this->form->rulesForKepOnly());

            // 2. Persist the final draft locally
            $draftContract = $this->handleDraftPersistence();

            // 3. Step 1 of eHealth Flow: Initialize Request
            // We pass $draftContract->type ('capitation' or 'reimbursement')
            $initResponse = EHealth::contract()->initializeRequest($draftContract->type)->validate();
            $eHealthRequestUuid = $initResponse['uuid'];
            Log::info('eHealth request initialized.', ['ehealth_uuid' => $eHealthRequestUuid]);

            // TODO: Add document upload logic here using $initResponse['upload_url']

            // 4. Build the final payload using the Form Object's logic
            $payloadToSign = $this->form->buildPayload(
                [
                    'contractor_owner_id'          => $this->legalEntity->getOwner()->uuid,
                    'dictionaries'                 => $this->dictionaries,
                    'statute_md5_path'             => $this->form->statute_md5->getRealPath(),
                    'additional_document_md5_path' => $this->form->additional_document_md5->getRealPath(),
                ]
            );

            // 5. Sign the payload
            $signedContent = signatureService()->signData(
                $payloadToSign,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                Auth::user()->party->tax_id // Assuming the signer is the logged-in user
            );

            // 6. Step 2 of eHealth Flow: Create Signed Request
            $eHealthResponse = EHealth::contract()->createSignedRequest(
                $eHealthRequestUuid,
                $draftContract->type,
                ['signed_content' => $signedContent]
            )->validate();

            // 7. Update local records
            $this->updateLocalContract($draftContract, $eHealthResponse);

            session()?->flash('success', __('contracts.sign_success'));
            $this->resetSignatureFields();

            return redirect()->route('contracts.index', ['legalEntity' => $this->legalEntity->uuid]);

        } catch (Exception $e) {
            $this->handleGeneralException($e);
        } catch (Throwable $e) {
            Log::critical('A critical throwable was caught during contract signing.', ['message' => $e->getMessage()]);
            $this->dispatch('flashMessage', ['message' => __('errors.unexpected_error'), 'type' => 'error', 'persistent' => true]);
            $this->dispatch('close-signature-modal');
        }
    }

    /**
     * Abstract method for draft persistence.
     * The concrete class (ContractCreate or ContractEdit) must implement this.
     */
    abstract protected function handleDraftPersistence(): Contract;

    /**
     * Updates the local contract with data from eHealth.
     */
    protected function updateLocalContract(Contract $contract, array $eHealthResponse): void
    {
        $contract->uuid = $eHealthResponse['uuid']; // This is now the eHealth UUID
        $contract->status = $eHealthResponse['status'];
        $contract->contract_number = $eHealthResponse['contract_number'];
        $contract->start_date = Carbon::parse($eHealthResponse['start_date']);
        $contract->end_date = Carbon::parse($eHealthResponse['end_date']);
        $contract->contractor_legal_entity_id = $eHealthResponse['contractor_legal_entity']['uuid'];
        $contract->contractor_owner_id = $eHealthResponse['contractor_owner']['uuid'];
        $contract->inserted_at = Carbon::now();
        $contract->save();
    }

    /**
     * Resets KEP fields.
     */
    public function resetSignatureFields(): void
    {
        $this->form->reset('keyContainerUpload', 'password', 'knedp');
    }

    // ==========================================================
    // EXTERNAL CONTRACTOR MODAL METHODS
    // ==========================================================
    public function findExternalLegalEntity(): void
    {
        $this->validate(['external_contractor_modal_state.edrpou' => 'required|string|digits:8']);
        $edrpou = $this->external_contractor_modal_state['edrpou'];
        $foundEntity = LegalEntitiesRequestApi::getLegalEntities($edrpou);

        if (empty($foundEntity['data'])) {
            $this->addError('modal.external_contractor_modal_state.edrpou', 'Організацію з таким ЄДРПОУ не знайдено.');

            return;
        }
        $this->external_contractor_modal_state['legal_entity_id'] = $foundEntity['data'][0]['id'];
        $this->external_contractor_modal_state['name'] = $foundEntity['data'][0]['name'];
    }

    public function getHealthcareServices($divisionUuid): void
    {
        if (!$divisionUuid) {
            $this->healthcareServices = null;

            return;
        }
        $division = Division::where('uuid', $divisionUuid)->first();
        if (!$division) {
            $this->healthcareServices = null;

            return;
        }

        $this->external_contractor_modal_state['divisions']['id'] = $division->uuid;
        $this->healthcareServices = $division->healthcareService()->get();
    }

    public function addExternalContractor(): void
    {
        $this->validate([]);

        if ($this->external_contractor_key !== '') {
            $this->external_contractors_list[$this->external_contractor_key] = $this->external_contractor_modal_state;
        } else {
            $this->external_contractors_list[] = $this->external_contractor_modal_state;
        }
        $this->resetExternalContractorForm();
        $this->closeModal();
    }

    public function editExternalContractor($key): void
    {
        $this->external_contractor_key = (string) $key;
        $this->external_contractor_modal_state = $this->external_contractors_list[$key];
        if (!empty($this->external_contractor_modal_state['division_id'])) {
            $this->getHealthcareServices($this->external_contractor_modal_state['division_id']);
        }
        $this->openModal('addExternalContractors');
    }

    public function deleteExternalContractor($key): void
    {
        unset($this->external_contractors_list[$key]);
    }

    protected function resetExternalContractorForm(): void
    {
        $this->external_contractor_key = '';
        $this->external_contractor_modal_state = [];
        $this->healthcareServices = null;
        $this->resetValidation();
    }

    /**
     * Centralized exception handler.
     */
    private function handleGeneralException(Exception $e): void
    {
        $message = match (true) {
            $e instanceof ValidationException => __('forms.validation_error_check_fields'),
            $e instanceof EHealthValidationException => $e->getTranslatedMessage(),
            $e instanceof EHealthResponseException => $e->getMessage(),
            $e instanceof ConnectionException => __('errors.ehealth_connection_error'),
            default => $e->getMessage(),
        };
        Log::error('Contract form error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $this->dispatch('flashMessage', ['message' => $message, 'type' => 'error', 'persistent' => true]);
        $this->dispatch('close-signature-modal');
    }
}
