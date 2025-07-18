<?php

declare(strict_types=1);

namespace App\Classes\eHealth;

use Closure;
use Illuminate\Http\Client\Response;

class EHealthResponse extends Response
{
    /**
     * The path to the data in the response.
     * This is used to access the actual data in the response body using array dot notation.
     */
    public const string DATA_PATH = 'data';

    protected ?Closure $validator = null;

    public function __construct($response, ?Closure $validator = null)
    {
        parent::__construct($response);
        $this->validator = $validator;
    }

    /**
     * Validate response data.
     */
    public function validate(): array
    {
        if (is_null($this->validator)) {
            throw new \RuntimeException('Validator is not implemented for this response.');
        }

        return call_user_func($this->validator, $this);
    }

    /**
     * @return array eHealth response actual data
     */
    public function getData(): array
    {
        return $this->json(self::DATA_PATH, []);
    }
}
