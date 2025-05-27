<?php

namespace App\Actions\EmployeeRequest;

use App\Services\EHealth\Api\EmployeeRequestApiService; // Ensure correct namespace for the new API service
use Illuminate\Support\Facades\Log;

class SubmitToEHealthAction
{
    public function execute(array $signedData): bool
    {
        try {
            // Instantiate the new EmployeeRequestApiService
            $eHealthApiService = new EmployeeRequestApiService();
            $response = $eHealthApiService->submitEmployeeRequest($signedData);

            // Check if the response indicates success
            if (isset($response['id'])) { // Assuming E-Health returns an ID upon successful creation
                Log::info('Employee request successfully submitted to E-Health.', ['ehealth_id' => $response['id']]);
                return true;
            }

            Log::error('E-Health API submission failed with unexpected response.', ['response' => $response]);
            return false;

        } catch (\Exception $e) {
            Log::error('Error submitting employee request to E-Health.', [
                'error' => $e->getMessage(),
                'signed_data' => $signedData,
                'trace' => $e->getTraceAsString(),
            ]);
            // Re-throw or return false based on desired error handling
            throw $e;
        }
    }
}
