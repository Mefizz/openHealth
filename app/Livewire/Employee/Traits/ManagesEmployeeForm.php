<?php

namespace App\Livewire\Employee\Traits;

use App\Core\Arr;
use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\Employee;
use App\Models\Employee\EmployeeRequest;
use App\Models\Revision;
use App\Rules\TwoLettersSixDigits;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
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
    public bool $lockPartyFields = false;
    public bool $showSignatureModal = false;

    /**
     * REFACTORED: Loads form data from a model by calling the central hydrate method.
     */
    public function loadEmployeeFromModel(): void
    {
        if ($this->employeeId && !$this->employee) {
            $this->employee = Employee::findOrFail($this->employeeId);
        }
        $this->form->hydrate($this->employee);
    }

    /**
     * REFACTORED: Loads form data from a request by calling the central hydrate method.
     */
    public function loadEmployeeFromRequest(): void
    {
        if ($this->employeeRequest) {
            $this->employeeRequestId = $this->employeeRequest->id;
            $this->form->hydrate($this->employeeRequest);
        }
    }

    public function addPhone(): void
    {
        $this->form->party['phones'][] = ['type' => 'MOBILE', 'number' => ''];
    }

    public function removePhone(int $index): void
    {
        if (isset($this->form->party['phones'][$index])) {
            unset($this->form->party['phones'][$index]);
            $this->form->party['phones'] = array_values($this->form->party['phones']);
        }
    }


    /**
     * It correctly handles creating a new request or updating an existing draft.
     */
    public function save(): void
    {
        if (isset($this->form->party['phones'])) {
            $cleanedPhones = [];
            foreach ($this->form->party['phones'] as $phone) {
                if (isset($phone['number']) && is_string($phone['number'])) {
                    $digits = preg_replace('/[^0-9]/', '', $phone['number']);
                    $phone['number'] = !empty($digits) ? '+' . $digits : '';
                }
                $cleanedPhones[] = $phone;
            }
            $this->form->party['phones'] = $cleanedPhones;
        }

        if (isset($this->form->documents) && is_array($this->form->documents)) {
            foreach ($this->form->documents as $key => $document) {
                if (!empty($document['issuedAt'])) {
                    $this->form->documents[$key]['issuedAt'] = Carbon::parse($document['issuedAt'])->format('Y-m-d');
                }
            }
        }

        try {
            $this->form->validate($this->form->rulesForSave());
            $preparedDataForDb = $this->form->getPreparedData();

            if ($this->employeeRequest) {
                // SCENARIO: Re-saving a PENDING request.
                DB::transaction(function () use ($preparedDataForDb) {

                    $requestAttributes = Arr::only($preparedDataForDb, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']);
                    $this->employeeRequest->fill($requestAttributes)->save();
                    if ($this->employeeRequest->party) {
                        $partyAttributes = Arr::only($preparedDataForDb, ['last_name', 'first_name', 'second_name', 'gender', 'birth_date', 'tax_id', 'no_tax_id', 'email', 'working_experience', 'about_myself']);
                        $this->employeeRequest->party->update($partyAttributes);
                    }
                    $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
                    if ($this->employeeRequest->revision) {
                        $this->employeeRequest->revision->update(['data' => $nestedDataForRevision]);
                    }
                });
            } else {
                // SCENARIO: Creating a NEW request for the first time.
                // This logic correctly uses the repository's store method.
                $this->employeeRequest = Repository::employee()->store(
                    $preparedDataForDb,
                    legalEntity(),
                    new EmployeeRequest(),
                    null,
                    true
                );
                $nestedDataForRevision = $this->prepareDataForRevision($preparedDataForDb);
                $this->saveRevisionForRequest($nestedDataForRevision);
            }
            if ($this->employeeRequest) {
                $this->employeeRequestId = $this->employeeRequest->id;
            }

            session()->flash('success', __('forms.employee_request_saved_successfully'));

        } catch (ValidationException $e) {
            $this->dispatch('employee-form-failed');
            session()->flash('error-modal', __('forms.validation_failed_check_form'));
            throw $e;
        } catch (\Exception $e) {
            $this->handleException($e);
            throw $e;
        }
    }

    /**
     * Helper to encapsulate saving the revision.
     */
    private function saveRevisionForRequest(array $formData): void
    {
        if (!$this->employeeRequest) {
            return;
        }

        $nestedData = $this->prepareDataForRevision($formData);

        $this->employeeRequest->revision()->updateOrCreate(
            ['revisionable_id' => $this->employeeRequest->id],
            [
                'data' => $nestedData,
                'status' => Revision::STATUS_PENDING,
            ]
        );
    }

    /**
     * Helper method to prepare the nested data structure required for a Revision.
     */
    protected function prepareDataForRevision(array $flatData): array
    {
        return [
            'employee_request_data' => Arr::only($flatData, ['position', 'employee_type', 'start_date', 'end_date', 'division_id']),
            'party' => $flatData['party'] ?? [],
            'documents' => $flatData['documents'] ?? [],
            'phones' => $flatData['party']['phones'] ?? [],
            'doctor' => $flatData['doctor'] ?? [],
        ];
    }

    public function sign()
    {
        try {
            if ($this->employeeRequestId && !$this->employeeRequest) {
                $this->employeeRequest = EmployeeRequest::find($this->employeeRequestId);
            }

            $this->save(); // Save latest changes before signing

            $this->form->validate($this->form->rulesForKepOnly());

            if (!$this->employeeRequest || !$this->employeeRequest->revision) {
                throw new \RuntimeException('Employee request and its revision must be saved before signing.');
            }

            $dataForSigning = $this->formatEHealthRequest($this->employeeRequest->revision->data);
            $signedContent = signatureService()->signData(
                $dataForSigning,
                $this->form->password,
                $this->form->knedp,
                $this->form->keyContainerUpload,
                'Person',
                $this->form->party['taxId']
            );

            if ($this->sendSignedContentToEhealth($signedContent)) {
                return redirect()->route('employee.index', ['legalEntity' => legalEntity()->id]);
            }

        } catch (Exception $e) {
            session()->flash('error-modal', $e->getMessage());
            $this->handleException($e);
        }
    }

    public function openSignatureModal(): void
    {
        try {
            $this->save();
            if ($this->employeeRequest) {
                $this->showSignatureModal = true;
            }
        } catch (Exception $e) {
            $this->handleException($e);
        }
    }

    public function closeSignatureModal()
    {
        $this->showSignatureModal = false;


        if ($this->employeeRequest && $this->employeeRequest->id) {
            return redirect()->route('employee.edit',
                                     ['employeeId' => $this->employeeRequest->id, 'legalEntity' => legalEntity()->id]);
        }
    }

    /**
     * REFACTORED: Now uses the form's unpackRevisionData helper.
     */
    private function formatEHealthRequest(array $revisionData): array
    {
        $unpackedData = $this->form->unpackRevisionData($revisionData);

        $employeeData = $unpackedData['employeeData'];
        $partyData = $unpackedData['partyData'];
        $documentsData = $unpackedData['documentsData'];
        $phonesData = $unpackedData['phonesData'];
        $doctorData = $unpackedData['doctorData'];

        $apiEmployeeRequest = [
            'position' => $employeeData['position'] ?? null,
            'status' => 'NEW',
            'employee_type' => $employeeData['employee_type'] ?? null,
            'legal_entity_id' => (string)($employeeData['legal_entity_id'] ?? legalEntity()->id),
            'start_date' => isset($employeeData['start_date']) ? Carbon::parse($employeeData['start_date'])->format('Y-m-d') : null,
        ];

        if (!empty($employeeData['end_date'])) {
            $apiEmployeeRequest['end_date'] = Carbon::parse($employeeData['end_date'])->format('Y-m-d');
        }

        $apiEmployeeRequest['party'] = [
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

            'phones' => array_map(
                fn($phone) => ['type' => $phone['type'], 'number' => $phone['number']],
                $phonesData
            ),

            'documents' => array_map(
                fn($doc) => [
                    'type' => $doc['type'],
                    'number' => $doc['number'],
                    'issued_by' => $doc['issued_by'] ?? null,
                    'issued_at' => isset($doc['issued_at']) && !empty($doc['issued_at']) ? Carbon::parse($doc['issued_at'])->format('Y-m-d') : null
                ],
                $documentsData
            ),
        ];

        if (($employeeData['employee_type'] ?? null) === 'DOCTOR' && !empty($doctorData)) {
            $doctorPayload = [];
            if (!empty($doctorData['educations'])) $doctorPayload['educations'] = $doctorData['educations'];
            if (!empty($doctorData['qualifications'])) $doctorPayload['qualifications'] = $doctorData['qualifications'];
            if (!empty($doctorData['specialities'])) $doctorPayload['specialities'] = $doctorData['specialities'];
            if (!empty($doctorData['science_degrees'])) $doctorPayload['science_degree'] = $doctorData['science_degrees'][0];

            if (!empty($doctorPayload)) $apiEmployeeRequest['doctor'] = $doctorPayload;
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
                $this->employeeRequest->legal_entity_uuid = $ehealthResponse['legal_entity_id'];
                $this->employeeRequest->updated_at = $ehealthResponse['updated_at'];
                $this->employeeRequest->save();
                session()->flash('success', __('forms.requestSignedAndSentToEHealth'));
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

    /**
     * A new helper method to run complex, cross-field validation.
     *
     * @throws ValidationException
     */
    protected function runCustomValidation(): void
    {
        $formData = $this->form->all();

        $validator = Validator::make($formData, [
            'documents.*.number' => [
                function($attribute, $value, $fail) use ($formData) {
                    if (data_get($formData, 'party.noTaxId', false)) {
                        $passportIndex = collect($formData['documents'])->search(fn($doc) => $doc['type'] === 'PASSPORT'
                        );

                        if ($passportIndex === false) {
                            $fail(__('validation.custom.passport_required_if_no_tax_id'));
                            return;
                        }

                        if ("documents.{$passportIndex}.number" === $attribute) {
                            $rule = new TwoLettersSixDigits();
                            $rule->validate($attribute, $value, $fail);
                        }
                    }
                }
            ],
        ]);
        $validator->validate();
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

    protected function dispatchErrorMessage(array $errors, string $prefix = ''): void
    {
        $errorMessage = collect($errors)->flatten()->implode(', ');
        session()->flash('error', $prefix . $errorMessage);
        $this->dispatch('employee-form-failed');
    }
}
