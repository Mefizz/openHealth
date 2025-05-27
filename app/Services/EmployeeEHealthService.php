<?php

namespace App\Services;

use App\Exceptions\EHealthApiException;
use App\Models\Employee\EmployeeRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmployeeEHealthService
{
    private const API_ENDPOINT = 'https://api.ehealth.gov.ua/api/employee-requests';
    private const TIMEOUT = 30;

    public function submitToEHealth(EmployeeRequest $request): EmployeeRequest
    {
        $payload = $this->buildPayload($request);

        try {
            $response = $this->makeApiRequest($payload);
            return $this->handleApiResponse($request, $response);
        } catch (EHealthApiException $e) {
            Log::error('EHealth API Error', [
                'error' => $e->getMessage(),
                'request_id' => $request->id,
                'payload' => $payload
            ]);
            throw $e;
        }
    }

    protected function makeApiRequest(array $payload): Response
    {
        return Http::withToken(config('services.ehealth.token'))
            ->timeout(self::TIMEOUT)
            ->retry(3, 100)
            ->post(self::API_ENDPOINT, $payload);
    }

    protected function handleApiResponse(EmployeeRequest $request, Response $response): EmployeeRequest
    {
        if ($response->failed()) {
            throw new EHealthApiException(
                "EHealth API request failed with status: {$response->status()}",
                $response->status()
            );
        }

        $responseData = $response->json();

        if (empty($responseData['data'])) {
            throw new EHealthApiException('Invalid response format from EHealth API');
        }

        return $this->updateEmployeeRequest($request, $responseData['data']);
    }

    protected function updateEmployeeRequest(EmployeeRequest $request, array $responseData): EmployeeRequest
    {
        $request->update([
            'ehealth_employee_request_id' => $responseData['id'] ?? null,
            'ehealth_employee_id' => $responseData['employee']['id'] ?? null,
            'status' => $responseData['status'] ?? 'NEW',
            'submitted_at' => now(),
        ]);

        return $request;
    }

    protected function buildPayload(EmployeeRequest $request): array
    {
        return [
            'employee_request' => [
                'party' => $this->getPartyData($request),
                'position' => $request->position,
                'division_id' => $request->division_id,
                'legal_entity_id' => config('ehealth.legal_entity_id'),
                'status' => 'NEW',
                'documents' => $this->getDocumentsData($request),
                // інші поля згідно API
            ]
        ];
    }

    protected function getPartyData(EmployeeRequest $request): array
    {
        return [
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'second_name' => $request->second_name,
            'birth_date' => $request->birth_date->toDateString(),
            'gender' => $request->gender,
            'email' => $request->email,
            'phones' => $this->getPhonesData($request),
            // інші поля
        ];
    }

    protected function getPhonesData(EmployeeRequest $request): array
    {
        return $request->party->phones->map(function ($phone) {
            return [
                'type' => $phone->type,
                'number' => $phone->number
            ];
        })->toArray();
    }

    protected function getDocumentsData(EmployeeRequest $request): array
    {
        return $request->party->documents->map(function ($document) {
            return [
                'type' => $document->type,
                'number' => $document->number,
                'issued_by' => $document->issued_by,
                'issued_at' => $document->issued_at?->toDateString(),
            ];
        })->toArray();
    }
}
