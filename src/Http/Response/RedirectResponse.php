<?php

namespace Tuto\Http\Response;

class RedirectResponse extends AbstractResponse
{
    public function __construct(
        int $code,
        string $location,
        string $httpVersion = 'HTTP/1.1',
        array $headers = [],
    ) {
        $headers['Location'] = $location;
        parent::__construct($code, $httpVersion, $headers);
    }
}
