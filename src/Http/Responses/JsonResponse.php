<?php

namespace Tuto\Http\Responses;

use JsonException;

class JsonResponse extends AbstractResponse
{
    /**
     * @throws JsonException
     */
    public function __construct(
        array $data = [],
        HttpCode $code = HttpCode::OK,
        array $headers = [],
        string $httpVersion = 'HTTP/2',
    ) {
        $body = json_encode($data, JSON_THROW_ON_ERROR);
        $headers['Content-Type'] = 'application/json';

        parent::__construct($body, $code, $headers, $httpVersion);
    }
}