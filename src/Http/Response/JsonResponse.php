<?php

namespace Tuto\Http\Response;

use JsonException;

class JsonResponse extends AbstractResponse
{
    /**
     * @throws JsonException
     */
    public function __construct(
        int $code,
        array $data = [],
        string $httpVersion = 'HTTP/1.1',
        array $headers = [],
    ) {
        $body = json_encode($data, JSON_THROW_ON_ERROR);
        $headers['Content-Type'] = 'application/json';

        parent::__construct($code, $httpVersion, $headers, $body);
    }
}
