<?php

namespace Tuto\Routing;

use InvalidArgumentException;
use Tuto\Collections\Collection;
use Tuto\Http\Requests\HttpMethod;
use Tuto\Http\Requests\Request;
use Tuto\Http\Requests\Uri;

class Route
{
    private const string PATH_PARAM_REGEX = '/\{([a-zA-Z0-9_\-]+)}/';

    /** @var callable|array{class-string, string} $handler */
    private $handler;

    /** @var Collection<string, string> $pathParameters */
    private Collection $pathParameters;

    /** @var Collection<string, string> $matches */
    private Collection $matches;

    public function __construct(
        private readonly HttpMethod $method,
        private readonly string $path,
        callable|array $handler,
        private readonly string|null $name = null,
    ) {
        $this->handler = $handler;
        $this->pathParameters = collect();
        $this->matches = collect();
    }

    /**
     * @return callable|array{class-string, string}
     */
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

    /**
     * @param string $parameter
     * @param string $regex
     * @return $this
     */
    public function where(string $parameter, string $regex): self
    {
        $this->pathParameters[$parameter] = $regex;
        return $this;
    }

    /**
     * @param Collection<string, string>|array<string, string> $parameters
     * @return void
     */
    public function setPathParameters(Collection|array $parameters): void
    {
        if (is_array($parameters)) {
            $parameters = collect($parameters);
        }
        $this->pathParameters = $parameters;
    }

    /**
     * @param Request $request
     * @return bool
     */
    public function match(Request $request): bool
    {

        if ($request->method !== $this->method) {
            return false;
        }

        $pathRegex = preg_replace_callback(
            self::PATH_PARAM_REGEX,
            fn (array $matches) => $this->transformPathMatcher($matches),
            $this->path,
        );

        $matches = [];
        if (!preg_match("#^{$pathRegex}$#", $request->uri->path, $matches)) {
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
     * @param bool $fullUri
     * @return string
     */
    public function generate(array $parameters, bool $fullUri): string
    {
        $baseUri = $fullUri ? Uri::trimPath(env('APP_URL', 'http://localhost')) : '';
        $generatedPath = "{$baseUri}/{$this->path}";

        $matches = [];
        if (!preg_match_all(self::PATH_PARAM_REGEX, $generatedPath, $matches)) {
            return $this->getUriWithQueryParams($generatedPath, $parameters);
        }

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
        $pathRegex = $pathParam ?? PathParameter::DEFAULT_PATH_REGEX;

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