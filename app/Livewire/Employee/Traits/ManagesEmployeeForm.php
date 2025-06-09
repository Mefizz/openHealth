<?php

namespace App\Livewire\Employee\Traits;

use App\Classes\Cipher\Api\CipherApi;
use App\Enums\Status;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Locked;
use Livewire\WithFileUploads;
use App\Repositories\Repository;

trait ManagesEmployeeForm
{
    use WithFileUploads;

    protected ?Employee $employee = null;

    #[Locked]
    public ?int $employeeId = null;

    public ?EmployeeRequest $employeeRequest = null;
    public bool $showSignatureBlock = false;

    /**
     * Load the employee model based on the locked employeeId.
     */
    public function loadEmployee(): void
    {
        if ($this->employeeId) {
            $this->employee = Employee::findOrFail($this->employeeId);
            $this->form->populateFromModel($this->employee);
        }
    }

    /**
     * Save or Update the employee data.
     */
    public function save(): void
    {
        try {
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();
            $preparedDataForDb['legal_entity_uuid'] = legalEntity()->uuid;
            $preparedDataForDb['legal_entity_id'] = legalEntity()->id;

            if ($this->employeeRequest) {
                // Re-saving a pending request.
                if ($this->employeeRequest->revision) {
                    $this->employeeRequest->revision->update(['data' => $preparedDataForDb]);
                }
            } else {
                // Creating a new request.
                if ($this->employee) {
                    $this->employeeRequest = Repository::employee()->createChangeRequestForExistingEmployee(
                        $preparedDataForDb,
                        $this->employee->uuid,
                        legalEntity()
                    );
                } else {
                    $this->employeeRequest = Repository::employee()->saveEmployeeData(
                        $preparedDataForDb,
                        legalEntity(),
                        new EmployeeRequest(),
                        null,
                        true
                    );
                }
            }
            session()->flash('success', __('forms.employee_request_saved_successfully'));
            $this->showSignatureBlock = true;
        } catch (Exception $e) {
            $this->handleException($e);
            if ($e instanceof ValidationException) throw $e;
        }
    }

    /**
     * Handles the signing process.
     * The return type has been removed to avoid type conflicts with Livewire's Redirector.
     */
    public function sign()
    {
        try {
            $this->save();
            $this->employeeRequest->refresh();
            $this->form->validate($this->form->rulesForKepOnly());

            $dataForSigning = $this->prepareDataForApiSigning($this->employeeRequest->revision->data);
            $base64FileContent = $this->form->getBase64KepFileContent();

            $signedContent = signatureService()->signData(
                $dataForSigning,
                $this->form->password,
                $this->form->knedp,
                $base64FileContent,
                CipherApi::SIGNATORY_INITIATOR_PERSON,
                $this->form->party['taxId']
            );

            if (is_array($signedContent) && isset($signedContent['errors'])) {
                $this->dispatchErrorMessage((array)$signedContent['errors'], __('forms.failed_to_sign_data'));
                return null;
            }

            if ($this->sendSignedContentToEhealth($signedContent)) {
                return redirect()->route('employee.index');
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }

        return null;
    }

    /**
     * Prepares data from a revision record for signing and sending to the eHealth API.
     */
    private function prepareDataForApiSigning(array $revisionData): array
    {
        $sourceData = $revisionData['employee_request_data'] ?? $revisionData;
        $partyData = $revisionData['party'] ?? ($sourceData['party'] ?? []);
        $documentsData = $revisionData['documents'] ?? ($sourceData['documents'] ?? []);
        $doctorData = $revisionData['doctor'] ?? ($sourceData['doctor'] ?? []);

        $apiEmployeeRequest = [
            'position' => $sourceData['position'] ?? null,
            'status' => 'NEW',
            'employee_type' => $sourceData['employee_type'] ?? null,
            'legal_entity_id' => (string)($sourceData['legal_entity_id'] ?? legalEntity()->id),
            'start_date' => isset($sourceData['start_date']) ? Carbon::parse($sourceData['start_date'])->format('Y-m-d') : null,
            'party' => [
                'first_name' => $partyData['first_name'] ?? null,
                'last_name' => $partyData['last_name'] ?? null,
                'second_name' => $partyData['second_name'] ?? null,
                'birth_date' => isset($partyData['birth_date']) ? Carbon::parse($partyData['birth_date'])->format('Y-m-d') : null,
                'gender' => $partyData['gender'] ?? null,
                'no_tax_id' => (bool)($partyData['no_tax_id'] ?? false),
                'tax_id' => $partyData['tax_id'] ?? null,
                'email' => $partyData['email'] ?? null,
                'working_experience' => isset($partyData['working_experience']) ? (int)$partyData['working_experience'] : null,
                'about_myself' => $partyData['about_myself'] ?? null,
                'phones' => array_map(fn($phone) => ['type' => $phone['type'], 'number' => $phone['number']], $partyData['phones'] ?? []),
                'documents' => array_map(fn($doc) => ['type' => $doc['type'], 'number' => $doc['number'], 'issued_by' => $doc['issued_by'], 'issued_at' => isset($doc['issued_at']) ? Carbon::parse($doc['issued_at'])->format('Y-m-d') : null], $documentsData),
            ],
        ];

        if (!empty($sourceData['end_date'])) {
            $apiEmployeeRequest['end_date'] = Carbon::parse($sourceData['end_date'])->format('Y-m-d');
        }

        if (($sourceData['employee_type'] ?? null) === 'DOCTOR') {
            $doctorPayload = [];
            if (!empty($doctorData['division_uuid'])) {
                $doctorPayload['division_id'] = $doctorData['division_uuid'];
            }
            if (!empty($doctorData['educations'])) {
                $doctorPayload['educations'] = $doctorData['educations'];
            }
            if (!empty($doctorData['qualifications'])) {
                $doctorPayload['qualifications'] = $doctorData['qualifications'];
            }
            if (!empty($doctorData['specialities'])) {
                $doctorPayload['specialities'] = $doctorData['specialities'];
            }
            if (!empty($doctorData['science_degrees'])) {
                $doctorPayload['science_degree'] = $doctorData['science_degrees'][0];
            }
            if (!empty($doctorPayload)) {
                $apiEmployeeRequest['doctor'] = $doctorPayload;
            }
        }
        return ['employee_request' => $apiEmployeeRequest];
    }

    /**
     * Sends the signed content to eHealth API and returns success status.
     */
    protected function sendSignedContentToEhealth(string $signedContent): bool
    {
        try {
            $ehealthResponse = EmployeeRequestApi::createEmployeeRequest(
                [
                    'signed_content' => $signedContent,
                    'signed_content_encoding' => 'base64',
                ]
            );
            if (isset($ehealthResponse['id'])) {
                $this->employeeRequest->uuid = $ehealthResponse['id'];
                $this->employeeRequest->inserted_at = $ehealthResponse['inserted_at'];
                $this->employeeRequest->status = Status::SIGNED;
                $this->employeeRequest->save();
                session()->flash('success', __('forms.request_signed_and_sent_to_ehealth'));
                $this->dispatch('signature-successful');
                return true;
            } else {
                $errorMessage = $ehealthResponse['error']['message'] ?? __('forms.failed_to_send_request_to_esoz_unknown_error');
                if (isset($ehealthResponse['error']['invalid']) && is_array($ehealthResponse['error']['invalid'])) {
                    $detailedErrors = collect($ehealthResponse['error']['invalid'])->map(fn($error) => $error['description'] ?? $error['rule'] ?? __('forms.details_unknown'))->implode('; ');
                    $errorMessage .= ' ' . $detailedErrors;
                }
                session()->flash('error', $errorMessage);
                $this->dispatch('employee-form-failed');
                return false;
            }
        } catch (Exception $e) {
            $this->handleException($e);
            return false;
        }
    }

    private function handleException(Exception $e): void
    {
        Log::error('Process failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        $message = $e instanceof ValidationException
            ? __('forms.validation_failed_check_form')
            : __('forms.failed_to_save_employee_unexpected_error');
        session()->flash('error', $message);
        $this->dispatch('employee-form-failed');
    }

    /**
     * Dispatches a structured error message to the session.
     */
    protected function dispatchErrorMessage(array $errors, string $prefix = ''): void
    {
        $errorMessage = collect($errors)->flatten()->implode(', ');
        session()->flash('error', $prefix . $errorMessage);
        $this->dispatch('employee-form-failed');
    }
}
