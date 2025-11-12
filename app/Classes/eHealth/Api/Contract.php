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

class Contract extends EHealthRequest
{
    /**
     * main endpoint
     */
    public const string URL = '/api/contract_requests';

    /**
     * get list
     *
     * @param array $filters
     * @param int $page
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function getMany(array $filters, int $page = 1): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateMany(...));
        $this->setDefaultPageSize();

        $mergedQuery = array_merge(
            $this->options['query'] ?? [],
            $filters,
            ['page' => $page]
        );

        return $this->get(self::URL, $mergedQuery);
    }

    /**
     * get details of 1 contract by 'uuid'
     *
     * @param string $uuid UUID контракту.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function getDetails(string $uuid): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateDetails(...));

        return $this->get(self::URL . '/' . $uuid);
    }

    /**
     * initiate request for creating cotract
     *
     * @param string $contractType 'capitation' або 'reimbursement'.
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function initializeRequest(string $contractType): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateInitialize(...));

        // URL: /api/contract_requests/capitation
        return $this->post(self::URL . '/' . $contractType, []);
    }

    /**
     * send signed contract
     *
     * @param string $uuid
     * @param string $contractType 'capitation' або 'reimbursement'.
     * @param array $payload
     * @return PromiseInterface|EHealthResponse
     * @throws ConnectionException
     */
    public function createSignedRequest(string $uuid, string $contractType, array $payload): PromiseInterface|EHealthResponse
    {
        $this->setValidator($this->validateCreateSigned(...));

        // URL: /api/contract_requests/capitation/{uuid}
        return $this->post(self::URL . '/' . $contractType . '/' . $uuid, $payload);
    }

    /**
     * validate getMany().
     */
    protected function validateMany(EHealthResponse $response): array
    {
        $transformedData = [];
        foreach ($response->getData() as $item) {
            $transformedData[] = self::replaceEHealthPropNames($item);
        }

        $validator = Validator::make($transformedData, [
            '*.uuid' => 'required|uuid',
            '*.status' => 'required|string',
            '*.contract_number' => 'required|string',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (getMany) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * validate getDetails()
     */
    protected function validateDetails(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());

        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (getDetails) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * validate initializeRequest().
     * eHealth return body of contract
     */
    protected function validateInitialize(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());

        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string|in:NEW',
            'contractor_legal_entity' => 'required|array',
            'contractor_legal_entity.uuid' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (initializeRequest) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     * validate createSignedRequest() response.
     * eHealth final contract object.
     */
    protected function validateCreateSigned(EHealthResponse $response): array
    {
        $transformedData = self::replaceEHealthPropNames($response->getData());

        $validator = Validator::make($transformedData, [
            'uuid' => 'required|uuid',
            'status' => 'required|string',
            'contract_number' => 'required|string',
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'required|date_format:Y-m-d',
            'contractor_legal_entity' => 'required|array',
            'contractor_legal_entity.uuid' => 'required|uuid',
            'contractor_owner' => 'required|array',
            'contractor_owner.uuid' => 'required|uuid',
        ]);

        if ($validator->fails()) {
            $error = 'EHealth Contract (createSignedRequest) validation failed: ' . implode(', ', $validator->errors()->all());
            Log::channel('e_health_errors')->error($error);
            throw ValidationException::withMessages(['ehealth_error' => $error]);
        }

        return $validator->validated();
    }

    /**
     *
     * @param array $properties
     * @return array
     */
    protected static function replaceEHealthPropNames(array $properties): array
    {
        $replaced = [];
        foreach ($properties as $name => $value) {
            if ($name === 'id') {
                $replaced['uuid'] = $value;
            } elseif (is_array($value) && isset($value['id'])) {
                $value['uuid'] = $value['id'];
                unset($value['id']);
                $replaced[$name] = $value;
            } else {
                $replaced[$name] = $value;
            }
        }

        return $replaced;
    }
}
