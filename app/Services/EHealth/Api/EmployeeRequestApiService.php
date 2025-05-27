<?php

namespace App\Services\EHealth\Api;

use App\Livewire\Employee\Forms\Api\EmployeeRequestApi; // Consider if this legacy class is still needed or can be removed
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EmployeeRequestApiService
{
    private PendingRequest $client;

    public function __construct()
    {
        $this->client = Http::withHeaders([
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
            // Add any necessary authentication headers here if they are static or can be resolved
            // e.g., 'Authorization' => 'Bearer ' . config('services.ehealth.api_token'),
        ])->baseUrl(config('services.ehealth.api_url'));
    }

    public function submitEmployeeRequest(array $signedData)
    {
        // Removed the legacy fallback as the user wants to use the new API.
        // If you still need a fallback for old API, you can re-introduce the if condition.
        try {
            $response = $this->client->post('/api/employee-requests', $signedData); // Correct endpoint based on Confluence

            if ($response->successful()) {
                return $response->json();
            }

            Log::error('eHealth API Error', [
                'status' => $response->status(),
                'response' => $response->body(),
                'data_sent' => $signedData, // Log the data sent for debugging
            ]);

            // Throw a more specific exception if possible
            throw new \Exception('API request failed: ' . $response->status() . ' - ' . $response->body());

        } catch (\Exception $e) {
            Log::error('Employee request API error', [
                'error' => $e->getMessage(),
                'data' => $signedData,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    // You can keep or remove these if they are no longer used or if EmployeeRequestApi is completely deprecated
    public function getEmployeeRequestById(string $id): array
    {
        // This might need to be updated to use the new $this->client if EmployeeRequestApi is removed.
        return EmployeeRequestApi::getEmployeeRequestById($id);
    }

    public function getEmployeesList(array $params = []): array
    {
        // This might need to be updated to use the new $this->client if EmployeeRequestApi is removed.
        return EmployeeRequestApi::getEmployeeRequestsList($params);
    }
}
