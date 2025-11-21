<?php

namespace Tuto\Http\Response;

use InvalidArgumentException;

abstract class AbstractResponse
{
    public const array HTTP_CODE = [
        // 2XX
        200 => 'OK',
        201 => 'Created',
        204 => 'No Content',
        // 3XX
        301 => 'Moved Permanently',
        302 => 'Found',
        304 => 'Not Modified',
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // 4XX
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        418 => 'I\'m a teapot',
        429 => 'Too Many Requests',
        // 5XX
        500 => 'Internal Server Error',
        503 => 'Service Unavailable',
    ];

    private const array NO_CONTENT_CODE = [204, 301, 302, 304, 307, 308];

    protected function __construct(
        private readonly int $code,
        private readonly string $httpVersion = 'HTTP/1.1',
        private readonly array $headers = [],
        private string $body = '',
    ) {
        if (!array_key_exists($this->code, self::HTTP_CODE)) {
            throw new InvalidArgumentException("HTTP Status code '{$this->code}' not supported");
        }

        if (in_array($this->code, self::NO_CONTENT_CODE)) {
            $this->body = '';
        }
    }

    public function renderHeaders(): void
    {
        header("{$this->httpVersion} {$this->code} " . self::HTTP_CODE[$this->code], true, $this->code);
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
