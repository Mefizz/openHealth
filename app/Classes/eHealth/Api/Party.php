<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\EHealthRequest;
use App\Classes\eHealth\EHealthResponse;
use GuzzleHttp\Promise\PromiseInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Handles API requests related to the eHealth 'Party' and 'Party Verification' endpoints.
 */
class Party extends EHealthRequest
{
    /**
     * The base URL for the party endpoints.
     */
    protected const string URL = '/api/parties';

    /**
     * Fetches a paginated list of party verification statuses.
     *
     * @param array $filters An array of filters to apply to the query (e.g., ['status' => 'APPROVED']).
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function getMany(array $filters = []): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateMany(...));
        $this->setDefaultPageSize();

        return $this->get(self::URL . '/verifications', $filters);
    }

    /**
     * Fetches the detailed verification status for a single party.
     *
     * @param string $uuid The UUID of the party.
     * @param array|null $query Optional query parameters.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function getDetails(string $uuid, array $query = null): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateDetails(...));

        return $this->get(self::URL . '/' . $uuid . '/verification', $query);
    }

    /**
     * Sends a request to update a party's verification status.
     *
     * @param string $uuid The UUID of the party to update.
     * @param array $data The data for the update request.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function update(string $uuid, array $data = []): PromiseInterface|EHealthResponse
    {
        // Note: Corrected the URL to include a slash before the UUID.
        return $this->patch(self::URL . '/' . $uuid . '/verification', $data);
    }

    /**
     * Validates the response for a single party's verification details.
     *
     * @param EHealthResponse $response The response from the eHealth API.
     * @return array
     * @throws ValidationException
     */
    protected function validateDetails(EHealthResponse $response): array
    {
        $data = $response->getData();

        $rules = [
            'verification_status' => 'required|string',
            'details' => 'required|array',
            'details.drfo.verification_status' => 'required|string',
            'details.dracs_death.verification_status' => 'required|string',
        ];

        return Validator::make($data, $rules)->validated();
    }

    /**
     * Validates the response for a list of party verification statuses.
     *
     * @param EHealthResponse $response The response from the eHealth API.
     * @return array The extracted 'data' array from the response.
     * @throws ValidationException
     */
    protected function validateMany(EHealthResponse $response): array
    {
        // The full response body is validated as the list is not wrapped in a 'data' key by this specific endpoint.
        $fullResponse = $response->json();

        $rules = [
            'data' => 'present|array',
            'data.*.party_id' => 'required|uuid',
            'data.*.verification_status' => 'required|string',
        ];

        $validator = Validator::make($fullResponse, $rules);

        try {
            $validatedData = $validator->validated();

            // Return only the 'data' array, as the listener expects.
            return $validatedData['data'];

        } catch (ValidationException $e) {
            Log::error('eHealth party list validation failed.', [
                'errors' => $e->errors(),
                'data_received' => $fullResponse
            ]);

            throw $e;
        }
    }
}
