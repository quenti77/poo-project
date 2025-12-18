<?php

namespace Tuto\Http\Components;

use DateTimeImmutable;

class Cookie
{
    public function __construct(
        public readonly string $name,
        public string $value,
        public DateTimeImmutable|null $expiredAt,
        public string $path = '',
        public string $domain = '',
        public bool $secure = true,
        public bool $httpOnly = true,
    ) {
    }

    public function export(): void
    {
        setcookie(
            name: $this->name,
            value: $this->value,
            expires_or_options: $this->expiredAt?->getTimestamp() ?? -1,
            path: $this->path,
            domain: $this->domain,
            secure: $this->secure,
            httponly: $this->httpOnly,
        );
    }

    public function remove(): void
    {
        $this->value = '';
        $this->expiredAt = null;
    }
}