<?php

namespace App\Livewire\Employee\Traits;

use App\Core\Arr;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Revision;
use App\Services\EHealth\EHealthSigningService;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;
use App\Repositories\Repository;
use Illuminate\Validation\ValidationException;

trait ManagesEmployeeForm
{
    use WithFileUploads;

    protected ?Employee $employee = null;
    protected ?EmployeeRequest $employeeRequest = null;

    abstract protected function getEmployeeRequestForSave(): ?EmployeeRequest;

    /**
     * The main save method.
     */
    public function save(): void
    {
        try {
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();

            // We fetch the model using the new abstract method
            $this->employeeRequest = $this->getEmployeeRequestForSave();

            if ($this->employeeRequest && is_null($this->employeeRequest->uuid)) {
                DB::transaction(fn() => $this->updateExistingDraft($preparedDataForDb));
            } else {
                DB::transaction(fn() => $this->createNewDraft($preparedDataForDb));
            }

            $this->dispatch('flashMessage', ['message' => __('forms.employee_request_saved_successfully'), 'type' => 'success']);

        } catch (Exception $e) {
            $this->handleException($e);
            throw $e;
        }
    }

    /**
     * Updates an existing draft request and its revision.
     */
    protected function updateExistingDraft(array $preparedDataForDb): void
    {
        $requestAttributes = Arr::only($preparedDataForDb, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
        $this->employeeRequest->fill($requestAttributes)->save();

        $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);

        if ($this->employeeRequest->revision) {
            $this->employeeRequest->revision->update(['data' => $nestedDataForRevision]);
        } else {
            $this->saveRevisionForRequest($this->employeeRequest, $nestedDataForRevision);
        }
    }

    /**
     * Helper method to create a new draft and its relations.
     */
    protected function createNewDraft(array $preparedDataForDb): void
    {
        $newRequest = Repository::employee()->store(
            $preparedDataForDb,
            legalEntity(),
            new EmployeeRequest(),
            null,
            true
        );

        $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
        $this->saveRevisionForRequest($newRequest, $nestedDataForRevision);

        $this->employeeRequest = $newRequest;
        if (property_exists($this, 'employeeRequestId')) {
            $this->employeeRequestId = $newRequest->id;
        }
    }

    /**
     * Helper method to prepare the nested data structure required for a Revision.
     */
    private function prepareDataForRevision(array $flatData): array
    {
        $employeeChunk = Arr::only($flatData, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
        $partyChunk = Arr::only($flatData, ['last_name', 'first_name', 'second_name', 'gender', 'birth_date', 'tax_id', 'no_tax_id', 'email', 'working_experience', 'about_myself']);
        $documentsChunk = $flatData['documents'] ?? [];
        $phonesChunk = $flatData['phones'] ?? [];
        $doctorChunk = $flatData['doctor'] ?? [];

        return [
            'employee_request_data' => $employeeChunk,
            'party' => $partyChunk,
            'documents' => $documentsChunk,
            'phones' => $phonesChunk,
            'doctor' => $doctorChunk,
        ];
    }

    /**
     * Helper to encapsulate saving the revision.
     */
    private function saveRevisionForRequest(EmployeeRequest $request, array $nestedData): void
    {
        $revision = new Revision();
        $revision->data = $nestedData;
        $revision->status = Revision::STATUS_PENDING;
        $request->revision()->save($revision);
    }

    /**
     * Resets only the fields related to the digital signature.
     */
    public function resetSignatureFields(): void
    {
        $this->form->reset('keyContainerUpload', 'password', 'knedp');
    }

    /**
     * It validates everything, saves, signs, and sends.
     */
    public function sign()
    {
        $this->save();

        try {
            $this->form->validate($this->form->rulesForKepOnly());

            if (!$this->employeeRequest) {
                // Re-fetch the request if it's not loaded, which might happen after save()
                if ($this->employeeRequestId) {
                    $this->employeeRequest = EmployeeRequest::find($this->employeeRequestId);
                }
                if (!$this->employeeRequest) {
                    throw new \RuntimeException('Employee request could not be found before signing.');
                }
            }

            $service = new EHealthSigningService();
            $success = $service->signAndSend(
                $this->employeeRequest,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload
            );

            if ($success) {
                session()->flash('success', __('forms.request_signed_and_sent_to_eHealth'));
                $this->resetSignatureFields();
                return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);
            }

            $this->dispatch('employee-form-failed');

        } catch (ValidationException $e) {
            session()->flash('error-modal', __('forms.validation_failed_check_form'));
            $this->dispatch('employee-form-failed');
            throw $e;
        } catch (Exception $e) {
            dd($e->getMessage());
            session()->flash('error-modal', $e->getMessage());
            $this->handleException($e);
        }
    }

    /**
     * Helper method for dispatching flash messages.
     */
    private function dispatchFlashMessage(string $message, string $type = 'success', array $errors = []): void
    {
        $this->dispatch('flashMessage', [
            'message' => $message,
            'type'    => $type,
            'errors'  => $errors
        ]);
    }

    /**
     * Centralized exception handler.
     */
    private function handleException(Exception $e): void
    {
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);

        $message = $e instanceof ValidationException
            ? __('forms.validation_failed_check_form')
            : __('forms.failed_to_save_employee_unexpected_error');

        $this->dispatchFlashMessage($message, 'error');
    }
}
