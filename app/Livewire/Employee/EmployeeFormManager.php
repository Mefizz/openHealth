<?php

namespace App\Livewire\Employee;

use App\Classes\Cipher\Api\CipherApi;
use App\Enums\Status;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Repositories\EmployeeRepository;
use App\Services\SignatureService;
use Exception;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use Livewire\WithFileUploads;

abstract class EmployeeFormManager extends EmployeeComponent
{
    use WithFileUploads;

    public ?Employee $employee = null;
    public ?EmployeeRequest $employeeRequest = null;
    public bool $showSignatureBlock = false;

    protected SignatureService $signatureService;

    public function boot(EmployeeRepository $employeeRepository): void
    {
        parent::boot($employeeRepository);
        $this->signatureService = app(SignatureService::class);
    }

    /**
     * Save or Update the employee data.
     * It creates a new EmployeeRequest in both cases.
     *
     * @return void
     * @throws ValidationException|Exception
     */
    public function save(): void
    {
        try {
            $this->form->validate($this->form->rulesForSave());

            $preparedDataForDb = $this->form->getPreparedData();

            $preparedDataForDb['legal_entity_uuid'] = legalEntity()->uuid;
            $preparedDataForDb['legal_entity_id']   = legalEntity()->id;

            $employeeUuid = $this->employee?->uuid;
            $isNewRequest = !$this->employee;

            // When editing, saveEmployeeData creates a new request linked to an existing employee.
            // When creating, it creates a new request for a new employee.
            $this->employeeRequest = $this->employeeRepository->saveEmployeeData(
                $preparedDataForDb,
                legalEntity(),
                new EmployeeRequest(),
                $employeeUuid,
                $isNewRequest
            );

            session()->flash('success', __('forms.employee_request_saved_successfully'));
            $this->showSignatureBlock = true;

        } catch (ValidationException $e) {
            $this->dispatch('employee-form-failed');
            session()->flash('error', __('forms.validation_failed_check_form'));
            throw $e;
        } catch (Exception $e) {
            Log::error('Failed to save employee: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatch('employee-form-failed');
            session()->flash('error', __('forms.failed_to_save_employee_unexpected_error'));
            $this->showSignatureBlock = false;
        }
    }

    /**
     * Handle the signing process for the employee request.
     *
     * @return void
     */
    public function sign(): void
    {
        if (!$this->employeeRequest) {
            session()->flash('error', __('forms.save_request_first'));
            $this->dispatch('employee-form-failed');
            return;
        }

        try {
            $this->form->validate(); // Full validation including KEP fields

            $base64FileContent           = $this->form->getBase64KepFileContent();
            $finalPreparedDataForSigning = $this->prepareDataForApiSigning($this->employeeRequest->revision->data);

            $signedContent = $this->signatureService->signData(
                $finalPreparedDataForSigning,
                $this->form->password,
                $this->form->knedp,
                $base64FileContent,
                CipherApi::SIGNATORY_INITIATOR_PERSON,
                $this->form->party['taxId']
            );

            if (is_array($signedContent) && isset($signedContent['errors'])) {
                $this->dispatchErrorMessage((array) $signedContent['errors'], __('forms.failed_to_sign_data'));
                return;
            }

            $this->sendSignedContentToEhealth($signedContent);

        } catch (ValidationException $e) {
            $this->dispatch('employee-form-failed');
            session()->flash('error', __('forms.signature_validation_error'));
            throw $e;
        } catch (Exception $e) {
            Log::error('Unexpected error during signature: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            $this->dispatchErrorMessage([$e->getMessage()], __('forms.unexpected_signature_error'));
        }
    }

    /**
     * Prepares data from a revision record for signing and sending to the eHealth API.
     *
     * @param array $revisionData Data stored in the revision.
     *
     * @return array The structured data ready for the API.
     */
    private function prepareDataForApiSigning(array $revisionData): array
    {
        // This method remains unchanged from your version. It's already well-structured.
        $requestData   = $revisionData['employee_request_data'] ?? [];
        $partyData     = $revisionData['party'] ?? [];
        $documentsData = $revisionData['documents'] ?? [];
        $phonesData    = $revisionData['phones'] ?? [];
        $doctorData    = $revisionData['doctor'] ?? [];

        $apiData = [];

        // ... logic for building $apiData ...
        // The implementation is identical to your provided code.
        // For brevity, it is omitted here, but should be copied from your EmployeeCreate class.
        $apiData['employee_request'] = [
            'position'        => $requestData['position'] ?? null,
            'status'          => $requestData['status'] ?? null,
            'employee_type'   => $requestData['employee_type'] ?? null,
            'legal_entity_id' => (string) ($requestData['legal_entity_id'] ?? null),
            'start_date'      => isset($requestData['start_date']) ? Carbon::parse($requestData['start_date'])->format(
                'Y-m-d'
            ) : null,
        ];

        if (!empty($requestData['end_date'])) {
            $apiData['employee_request']['end_date'] = Carbon::parse($requestData['end_date'])->format('Y-m-d');
        }
        if (!empty($doctorData['division_uuid'])) {
            $apiData['employee_request']['division_id'] = $doctorData['division_uuid'];
        }

        $apiData['employee_request']['party'] = [
            'first_name'         => $partyData['first_name'] ?? null,
            'last_name'          => $partyData['last_name'] ?? null,
            'second_name'        => $partyData['second_name'] ?? null,
            'birth_date'         => isset($partyData['birth_date']) ? Carbon::parse($partyData['birth_date'])->format(
                'Y-m-d'
            ) : null,
            'gender'             => $partyData['gender'] ?? null,
            'no_tax_id'          => (bool) ($partyData['no_tax_id'] ?? false),
            'tax_id'             => $partyData['tax_id'] ?? null,
            'email'              => $partyData['email'] ?? null,
            'working_experience' => isset($partyData['working_experience']) ? (int) $partyData['working_experience'] : null,
            'about_myself'       => $partyData['about_myself'] ?? null,
            'phones'             => array_map(fn($phone) => ['type' => $phone['type'], 'number' => $phone['number']],
                $phonesData),
            'documents'          => array_map(fn($doc) => [
                'type'      => $doc['type'],
                'number'    => $doc['number'],
                'issued_by' => $doc['issued_by'],
                'issued_at' => isset($doc['issued_at']) ? Carbon::parse($doc['issued_at'])->format('Y-m-d') : null,
            ], $documentsData),
        ];

        if (($apiData['employee_request']['employee_type'] ?? null) === 'DOCTOR') {
            $apiData['employee_request']['doctor'] = [];

            $apiData['employee_request']['doctor']['educations'] = array_map(fn($edu) => [
                'country'          => $edu['country'],
                'city'             => $edu['city'],
                'institution_name' => $edu['institution_name'],
                'speciality'       => $edu['speciality'],
                'degree'           => $edu['degree'],
                'issued_date'      => isset($edu['issued_date']) ? Carbon::parse($edu['issued_date'])->format(
                    'Y-m-d'
                ) : null,
                'diploma_number'   => $edu['diploma_number'],
            ], $doctorData['educations'] ?? []);

            $apiData['employee_request']['doctor']['qualifications'] = array_map(fn($qual) => [
                'type'               => $qual['type'],
                'institution_name'   => $qual['institution_name'],
                'speciality'         => $qual['speciality'],
                'issued_date'        => isset($qual['issued_date']) ? Carbon::parse($qual['issued_date'])->format(
                    'Y-m-d'
                ) : null,
                'certificate_number' => $qual['certificate_number'],
                'additional_info'    => $qual['additional_info'] ?? '',
            ], $doctorData['qualifications'] ?? []);

            $apiData['employee_request']['doctor']['specialities'] = array_map(fn($spec) => [
                'speciality'         => $spec['speciality'],
                'speciality_officio' => (bool) ($spec['speciality_officio'] ?? false),
                'level'              => $spec['level'],
                'qualification_type' => $spec['qualification_type'],
                'attestation_name'   => $spec['attestation_name'],
                'attestation_date'   => isset($spec['attestation_date']) ? Carbon::parse($spec['attestation_date'])
                    ->format('Y-m-d') : null,
                'certificate_number' => $spec['certificate_number'],
            ], $doctorData['specialities'] ?? []);

            if (!empty($doctorData['science_degrees'])) {
                $sciDeg                                                  = $doctorData['science_degrees'][0];
                $apiData['employee_request']['doctor']['science_degree'] = [
                    'degree'           => $sciDeg['degree'],
                    'country'          => $sciDeg['country'],
                    'city'             => $sciDeg['city'],
                    'issued_date'      => isset($sciDeg['issued_date']) ? Carbon::parse($sciDeg['issued_date'])->format(
                        'Y-m-d'
                    ) : null,
                    'institution_name' => $sciDeg['institution_name'],
                    'speciality'       => $sciDeg['speciality'],
                    'diploma_number'   => $sciDeg['diploma_number'],
                ];
            }
        }

        return $apiData;
    }


    /**
     * Dispatches an error message to the session.
     */
    protected function dispatchErrorMessage(array $errors, string $prefix = ''): void
    {
        // This method remains unchanged
        $errorMessage = collect($errors)->flatten()->implode(', ');
        session()->flash('error', $prefix . $errorMessage);
        $this->dispatch('employee-form-failed');
    }

    /**
     * Send the signed content to eHealth API.
     */
    protected function sendSignedContentToEhealth(string $signedContent): void
    {
        // This method remains unchanged
        try {
            $ehealthResponse = EmployeeRequestApi::createEmployeeRequest(
                [
                    'signed_content'          => $signedContent,
                    'signed_content_encoding' => 'base64',
                ]
            );

            if (isset($ehealthResponse['id'])) {
                $this->employeeRequest->uuid              = $ehealthResponse['id'];
                $this->employeeRequest->inserted_at       = $ehealthResponse['inserted_at'];
                $this->employeeRequest->legal_entity_uuid = $ehealthResponse['legal_entity_id'];
                $this->employeeRequest->updated_at        = $ehealthResponse['updated_at'];
                $this->employeeRequest->status            = Status::SIGNED;

                $this->employeeRequest->save();

                session()->flash('success', __('forms.request_signed_and_sent_to_ehealth'));

                $this->dispatch('signature-successful');
                $this->redirectRoute('employee.index');
            } else {
                $errorMessage = $ehealthResponse['error']['message'] ?? __(
                    'forms.failed_to_send_request_to_esoz_unknown_error'
                );
                if (isset($ehealthResponse['error']['invalid']) && is_array($ehealthResponse['error']['invalid'])) {
                    $detailedErrors = collect($ehealthResponse['error']['invalid'])->map(function($error) {
                        return $error['description'] ?? $error['rule'] ?? __('forms.details_unknown');
                    })->implode('; ');
                    $errorMessage   .= ' ' . $detailedErrors;
                } else if (isset($ehealthResponse['message'])) {
                    $errorMessage = $ehealthResponse['message'];
                }

                session()->flash('error', $errorMessage);
                $this->dispatch('employee-form-failed');
            }
        } catch (Exception $e) {
            Log::error('Unexpected eHealth API error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            session()->flash('error', __('forms.unexpected_esoz_error'));
            $this->dispatch('employee-form-failed');
        }
    }
}
