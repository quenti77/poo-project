<?php

namespace Tuto\Http\Requests;

use InvalidArgumentException;

class Uri
{
    public function __construct(
        public readonly string|null $schema,
        public readonly string|null $host,
        public readonly int|null $port,
        public readonly string|null $user,
        public readonly string|null $password,
        public readonly string $path,
        public readonly string|null $query,
        public readonly string|null $fragment,
    ) {
    }

    public static function fromString(string $uri): self
    {
        $parseUri = parse_url($uri);
        if ($parseUri === false) {
            throw new InvalidArgumentException("Invalid URI : '{$uri}'");
        }

        return new self(
            schema: $parseUri['scheme'] ?? null,
            host: $parseUri['host'] ?? null,
            port: $parseUri['port'] ?? null,
            user: $parseUri['user'] ?? null,
            password: $parseUri['pass'] ?? null,
            path: self::trimPath($parseUri['path'] ?? null),
            query: $parseUri['query'] ?? null,
            fragment: $parseUri['fragment'] ?? null,
        );
    }

    public static function trimPath(string $path): string
    {
        return trim(trim($path, '/'));
    }
}