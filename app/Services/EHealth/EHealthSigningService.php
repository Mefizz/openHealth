<?php

namespace App\Services\EHealth;

use App\Livewire\Employee\Forms\Api\EmployeeRequestApi;
use App\Models\Employee\EmployeeRequest;
use Exception;
use Illuminate\Support\Carbon;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

class EHealthSigningService
{
    /**
     * Main method to sign and send the employee request.
     *
     * @param EmployeeRequest $request The employee request with its revision data.
     * @param string $password The password for the digital signature key.
     * @param string $knedp The KNEDP provider.
     * @param TemporaryUploadedFile $keyContainer The uploaded key file.
     * @return bool Returns true on success, false on failure.
     * @throws Exception
     */
    public function signAndSend(EmployeeRequest $request, string $password, string $knedp, TemporaryUploadedFile $keyContainer): bool
    {
        if (!$request->revision?->data) {
            throw new \RuntimeException('Cannot sign a request without revision data.');
        }

        $dataForSigning = $this->formatEHealthRequest($request->revision->data);
        $taxId = $request->revision->data['party']['tax_id'] ?? null;

        if (is_null($taxId)) {
            throw new \RuntimeException('Tax ID is missing from revision data.');
        }

        $signedContent = signatureService()->signData(
            $dataForSigning,
            $password,
            $knedp,
            $keyContainer,
            'Person',
            $taxId
        );

        return $this->sendSignedContentToEhealth($request, $signedContent);
    }

    /**
     * Sends the signed content to the eHealth API.
     * Updates the request model on success.
     *
     * @param EmployeeRequest $request
     * @param string $signedContent
     * @return bool
     * @throws Exception
     */
    private function sendSignedContentToEhealth(EmployeeRequest $request, string $signedContent): bool
    {
        $ehealthResponse = EmployeeRequestApi::createEmployeeRequest([
                                                                         'signed_content' => $signedContent,
                                                                         'signed_content_encoding' => 'base64',
                                                                     ]);

        if (isset($ehealthResponse['id'])) {
            $request->uuid = $ehealthResponse['id'];
            $request->inserted_at = $ehealthResponse['inserted_at'];
            $request->legal_entity_uuid = $ehealthResponse['legal_entity_id'];
            $request->updated_at = $ehealthResponse['updated_at'];
            $request->save();
            return true;
        }

        $errorMessage = $ehealthResponse['error']['message'] ?? __('forms.failed_to_send_request_to_esoz_unknown_error');
        if (isset($ehealthResponse['error']['invalid']) && is_array($ehealthResponse['error']['invalid'])) {
            $detailedErrors = collect($ehealthResponse['error']['invalid'])->map(fn($error) => $error['description'] ?? $error['rule'] ?? __('forms.details_unknown'))->implode('; ');
            $errorMessage .= ' ' . $detailedErrors;
        }
        session()->flash('error', $errorMessage);
        return false;
    }

    /**
     * Formats the revision data into the structure required by the E-Health API.
     *
     * @param array $revisionData
     * @return array
     */
    private function formatEHealthRequest(array $revisionData): array
    {
        $employeeData = $revisionData['employee_request_data'];
        $partyData = $revisionData['party'];
        $documentsData = $revisionData['documents'];
        $phonesData = $revisionData['phones'];
        $doctorData = $revisionData['doctor'];

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
}
