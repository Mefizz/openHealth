<?php

declare(strict_types=1);

namespace App\Classes\eHealth\Api;

use App\Classes\eHealth\Exceptions\ApiException;
use App\Classes\eHealth\Request;
use Symfony\Component\HttpFoundation\Request as RequestHttp;

class ServiceApi
{
    protected const string ENDPOINT_SERVICE = '/api/services';

    /**
     * This web service returns a catalog of services that could be submitted in eHealth. The catalog has a tree data structure.
     * Each node represents group of services (or subgroup), except end-node, that represents services themselves.
     * Maximum nesting level is 4.
     *
     * @param  array  $params
     * @return array
     * @throws ApiException
     */
    public static function getServiceDictionary(array $params = []): array
    {
        return new Request(RequestHttp::METHOD_GET, self::ENDPOINT_SERVICE, $params)->sendRequest();
    }
}
