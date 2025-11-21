<?php

namespace Tuto\Routing;

use InvalidArgumentException;
use Tuto\Collection\Collection;
use Tuto\Http\Request;

class Route
{
    /** @var callable|array $handler */
    private $handler;

    /** @var Collection<string, string> $pathParameters */
    private Collection $pathParameters;

    /** @var Collection<string, string> $matches */
    private Collection $matches;

    public function __construct(
        private readonly string $method,
        private readonly string $path,
        callable|array $handler,
        private readonly string|null $name = null,
    ) {
        if (!in_array($this->method, Request::METHODS)) {
            throw new InvalidArgumentException("Invalid HTTP method '{$this->method}'");
        }

        $this->handler = $handler;
        $this->pathParameters = new Collection();
        $this->matches = new Collection();
    }

    public function getHandler(): callable|array
    {
        return $this->handler;
    }

    /**
     * @return Collection<string, string>
     */
    public function getMatches(): Collection
    {
        return $this->matches;
    }

    public function where(string $parameter, string $regex): self
    {
        $this->pathParameters[$parameter] = $regex;
        return $this;
    }

    public function match(Request $request): bool
    {
        $pathRegex = preg_replace_callback(
            '/\{([a-zA-Z0-9_\-]+)}/',
            fn (array $matches) => $this->transformPathMatcher($matches),
            $this->path,
        );

        $matches = [];
        if (!preg_match("#^{$pathRegex}$#", $request->uri, $matches)) {
            return false;
        }

        array_shift($matches);
        foreach ($matches as $key => $value) {
            if (is_string($key)) {
                $this->matches[$key] = $value;
            }
        }

        return true;
    }

    /**
     * @param array<string, string> $parameters
     * @return string
     */
    public function generate(array $parameters): string
    {
        $matches = [];
        if (!preg_match_all('/\{([a-zA-Z0-9_\-]+)}/', $this->path, $matches)) {
            return $this->getUriWithQueryParams($this->path, $parameters);
        }

        $generatedPath = $this->path;
        $replaceParameters = array_combine($matches[0], $matches[1]);
        foreach ($replaceParameters as $search => $key) {
            $value = $parameters[$key] ?? throw new InvalidArgumentException("Parameter '{$key}' must be defined");
            $generatedPath = str_replace($search, urlencode($value), $generatedPath);

            unset($parameters[$key]);
        }

        return $this->getUriWithQueryParams($generatedPath, $parameters);
    }

    /**
     * @param array<int|string, string> $matches
     * @return string
     */
    private function transformPathMatcher(array $matches): string
    {
        array_shift($matches);
        $parameter = $matches[0] ?? null;

        $pathParam = $this->pathParameters[$parameter] ?? null;
        $pathRegex = $pathParam ?? '[^/]+';

        return "(?P<{$parameter}>{$pathRegex})";
    }

    /**
     * @param string $uri
     * @param array<string, string> $queryParams
     * @return string
     */
    private function getUriWithQueryParams(string $uri, array $queryParams): string
    {
        return empty($queryParams)
            ? $uri
            : sprintf("%s?%s", $uri, http_build_query($queryParams));
    }
}