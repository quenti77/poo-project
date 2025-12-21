<?php

namespace Tuto\Http\Responses;

class AbstractResponse
{
    protected function __construct(
        private string $body = '',
        private readonly HttpCode $code = HttpCode::OK,
        private readonly array $headers = [],
        private readonly string $httpVersion = 'HTTP/2',
    ) {
        if ($this->code->hasNoContent()) {
            $this->body = '';
        }
    }

    public function renderHeaders(): void
    {
        header("{$this->httpVersion} {$this->code->value} {$this->code->label()}", true, $this->code->value);
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
    }

    public function getBody(): string
    {
        return $this->body;
    }
}