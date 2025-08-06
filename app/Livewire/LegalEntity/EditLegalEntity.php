<?php

namespace App\Livewire\LegalEntity;

use Exception;
use Illuminate\Support\Arr;
use App\Models\Employee\Employee;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use App\Models\LegalEntity as LegalEntityModel;

class EditLegalEntity extends LegalEntity
{
    public function mount(?LegalEntityModel $legalEntity = null): void
    {
        $this->legalEntity = $this->getLegalEntity();

        parent::mount();

        $this->getLegalEntityForm();
    }

    /**
     * Try to get the LegalEntity assigned for the user
     *
     * @return LegalEntityModel|null
     */
    protected function getLegalEntity(): ?LegalEntityModel
    {
        return $this->getLegalEntityFromAuth();
    }

    protected function setLegalEntity(): bool
    {
        $isNotNew = parent::setLegalEntity();

        if ($isNotNew) {
            $this->mergeAddress($this->convertArrayKeysToCamelCase($this->legalEntity->toArray())['address'] ?? []);
        }

        return $isNotNew;
    }

    /**
     * Retrieves the legal entity form data.
     */
    protected function getLegalEntityForm(): void
    {
        $this->setLegalEntity(); // Retrieve basic legal entity data
        $this->getLicenseForm(); // Get the license form data
        $this->getArchiveForm(); // Get the archive form data
        $this->getOwnerLegalEntity(); // Get the owner's legal entity data
        $this->getAccreditationForm(); // Get the accreditation form data status
    }

    /**
     * Retrieves and sets only specific fields related to the license from the legal entity form.
     */
    protected function getLicenseForm(): void
    {
        $license = $this->legalEntity->licenses()?->first();

        if ($license) {
            $this->legalEntityForm->license = Arr::only($this->convertArrayKeysToCamelCase($license->toArray()),
            [
                'type',
                'licenseNumber',
                'issuedBy',
                'issuedDate',
                'expiryDate',
                'activeFromDate',
                'whatLicensed',
                'orderNo'
            ]);
        }
    }

    /**
     * Retrieves and formats specific fields from the archive form.
     */
    protected function getArchiveForm(): void
    {
        // Extracting only 'date' and 'place' fields from the first element of the archive
        if (!empty($this->legalEntityForm->archive)) {
            // if the legal entity has an archive, the 'archivationShow' property is set to true
            $this->legalEntityForm->archivationShow = true;
        }
    }

    /**
     * Get the accreditation status of the legal entity
     * (if the legal entity has an accreditation, the 'accreditationShow' property is set to true)
     *
     * @return void
     */
    protected function getAccreditationForm(): void
    {
        if (!empty($this->legalEntityForm->accreditation)) {
            $this->legalEntityForm->accreditationShow = true;
        }
    }

    protected function getOwnerLegalEntity(): void
    {
        $owner = $this->legalEntity->getOwner();

        if (!$owner->exists()) {
            return;
        }

        $ownerData = $this->prepareOwnerData($owner);

        $this->legalEntityForm->owner = array_merge($this->legalEntityForm->owner ?? [], $ownerData);
    }

    private function prepareOwnerData(Employee $owner): array
    {
        $ownerData = $owner->party->toArray() ?? [];

        $ownerData['documents'] = $this->prepareDocumentsData($ownerData['documents']);
        $ownerData['position'] = $owner->position;
        $ownerData['employee_id'] = $owner->uuid;

        return $ownerData;
    }

    private function prepareDocumentsData(array $documents): array
    {
        if (empty($documents)) {
            return [];
        }

        return $this->convertArrayKeysToCamelCase($documents[0]);
    }

    /**
     * The main action method for updating a legal entity.
     */
    public function updateLegalEntity(): ?RedirectResponse
    {
        // Clear any previous alerts before a new attempt.
        $this->dismissAlert();

        // Run the form-specific validation for editing.
        $this->legalEntityForm->onEditValidate();
        if ($this->getErrorBag()->isNotEmpty()) {
            $this->dispatchBrowserEvent('scroll-to-error');
            return null;
        }

        // The trait's signAndSubmit() method handles all the complex logic.
        $result = $this->signAndSubmit();

        if (is_null($result)) {
            // An error occurred and the alert is already displayed. NO REDIRECT.
            return null;
        }

        // If the submission was successful, process the result.
        try {
            $data = $result['request'];
            $response = $this->filterUnprovidedFields($result['response'], $data);

            $legalEntity = LegalEntity::where(['uuid' => $response['data']['id'] ])->firstOrFail();
            $legalEntity->client_secret = $response['urgent']['security']['client_secret'] ?? $response['urgent']['security']['secret_key'] ?? null;
            $legalEntity->save();
            $legalEntity->refresh();

            DB::transaction(function() use($response, $data) {
                $this->modifyLegalEntity($response);
                $user = Auth::user();
                $this->createEmployeeRequest($this->legalEntity, $data, $response['urgent']['employee_request_id'], $user?->id ?? null);
            });

        } catch (Exception $err) {
            // Catch any errors during the final DB operations.
            Log::error(__('forms.errors.update_data', [], 'en'), ['error' => $err->getMessage()]);
            $this->showAlert(__('forms.errors.update_data'), 'error');
            return null;
        }

        // On full success, redirect with a success message (this uses the session-based alert).
        return Redirect::route('legal-entity.edit', [legalEntity()])
            ->with('success', __('forms.update_successfull'));
    }

    public function render()
    {
        return view('livewire.legal-entity.edit-legal-entity', ['isEdit' => true]);
    }
}
