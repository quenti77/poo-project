<?php

namespace Tuto\Http\Requests;

use Tuto\Http\Components\BodyParameters;
use Tuto\Http\Components\Cookies;
use Tuto\Http\Components\FileParameters;
use Tuto\Http\Components\Headers;
use Tuto\Http\Components\QueryParameters;
use Tuto\Http\Components\Session;
use Tuto\Routing\Route;

class Request
{
    private const string OVERRIDE_METHOD = '__method';

    /** @var Route|null $currentRoute */
    private Route|null $currentRoute = null;

    public function __construct(
        public readonly HttpMethod $method,
        public readonly Uri $uri,
        public readonly Headers $headers,
        public readonly QueryParameters $query,
        public readonly BodyParameters $body,
        public readonly FileParameters $files,
        public readonly Cookies $cookies,
        public readonly Session $session,
    ) {
    }

    /**
     * Create a request with all globals data from PHP
     *
     * @return self
     */
    public static function fromGlobals(): self
    {
        $method = HttpMethod::from(strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = Uri::fromString($_SERVER['REQUEST_URI'] ?? '');

        $query = QueryParameters::fromGlobals();
        $body = BodyParameters::fromGlobals();
        if ($method->has('GET', 'POST')) {
            if ($query->hasKeys(self::OVERRIDE_METHOD)) {
                $method = HttpMethod::from(strtoupper($query[self::OVERRIDE_METHOD]));
            } elseif ($body->hasKeys(self::OVERRIDE_METHOD)) {
                $method = HttpMethod::from(strtoupper($body[self::OVERRIDE_METHOD]));
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

    public static function fromRoute(HttpMethod $method, string $path): self
    {
        return new self(
            method: $method,
            uri: Uri::fromString($path),
            headers: Headers::fromGlobals(),
            query: QueryParameters::fromGlobals(),
            body: BodyParameters::fromGlobals(),
            files: FileParameters::fromGlobals(),
            cookies: Cookies::fromGlobals(),
            session: Session::fromGlobals(),
        );
    }

    /**
     * @return bool
     */
    public function isJson(): bool
    {
        $accept = $this->headers['Accept'] ?? $this->headers['accept'] ?? '';
        $contentType = $this->headers['Content-Type'] ?? $this->headers['content-type'] ?? '';

        return str_contains($accept, 'application/json')
            || str_contains($contentType, 'application/json')
            || (isset($this->headers['X-Requested-With']) && $this->headers['X-Requested-With'] === 'XMLHttpRequest')
            || (isset($this->headers['x-requested-with']) && $this->headers['x-requested-with'] === 'XMLHttpRequest');
    }

    /**
     * @return Route|null
     */
    public function getCurrentRoute(): Route|null
    {
        return $this->currentRoute;
    }

    /**
     * @param Route $route
     * @return void
     */
    public function updateCurrentRoute(Route $route): void
    {
        $this->currentRoute = $route;
    }
}
