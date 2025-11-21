<?php

namespace Tuto\Http;

use InvalidArgumentException;
use Tuto\Http\Components\BodyParameters;
use Tuto\Http\Components\Cookies;
use Tuto\Http\Components\FileParameters;
use Tuto\Http\Components\Headers;
use Tuto\Http\Components\QueryParameters;
use Tuto\Http\Components\Session;

class Request
{
    public const array METHODS = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'HEAD', 'OPTIONS'];

    public function __construct(
        public readonly string $method,
        public readonly string $uri,
        public readonly Headers $headers,
        public readonly QueryParameters $query,
        public readonly BodyParameters $body,
        public readonly FileParameters $files,
        public readonly Cookies $cookies,
        public readonly Session $session,
    ) {
        if (!in_array($this->method, self::METHODS)) {
            throw new InvalidArgumentException("Invalid HTTP method '{$this->method}'");
        }
    }

    public static function fromGlobals(): self
    {
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $uri = parse_url(self::trimPath($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_PATH);

        $query = QueryParameters::fromGlobals();
        $body = BodyParameters::fromGlobals();
        if (in_array($method, ['GET', 'POST'])) {
            $queryMethod = mb_strtoupper($query['__method'] ?? '');
            $bodyMethod = mb_strtoupper($body['__method'] ?? '');

            if (in_array($queryMethod, self::METHODS, true)) {
                $method = $queryMethod;
            } elseif (in_array($bodyMethod, self::METHODS, true)) {
                $method = $queryMethod;
            }
        }

        return new self(
            method: $method,
            uri: $uri,
            headers: Headers::fromGlobals(),
            query: $query,
            body: $body,
            files: FileParameters::fromGlobals(),
            cookies: Cookies::fromGlobals(),
            session: Session::fromGlobals(),
        );
    }

    public static function trimPath(string $path): string
    {
        return trim($path, '/');
    }
}