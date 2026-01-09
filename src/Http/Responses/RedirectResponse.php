<?php

namespace Tuto\Http\Responses;

class RedirectResponse extends AbstractResponse
{
    public function __construct(
        string $location,
        HttpCode $code = HttpCode::FOUND,
        array $headers = [],
        string $httpVersion = 'HTTP/2',
    ) {
        $headers['Location'] = $location;
        parent::__construct('', $code, $headers, $httpVersion);
    }
}
