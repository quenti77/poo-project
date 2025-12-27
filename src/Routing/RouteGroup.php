<?php

namespace Tuto\Routing;

use Tuto\Collections\Collection;
use Tuto\Http\Requests\Uri;
use Tuto\Routing\Middleware\MiddlewareInterface;

class RouteGroup
{
    /**
     * @param string $prefix
     * @param string $name
     * @param Collection $parameters
     */
    public function __construct(
        private string $prefix,
        private string $name,
        private readonly Collection $parameters,
        private readonly Collection $middlewares,
    ) {
        $this->prefix = Uri::trimPath($this->prefix);
        $this->name = trim($this->name);
    }

    /**
     * @param array{
     *     prefix?: string,
     *     name?: string,
     *     where?: array<string, string>,
     *     middleware?: array<int, MiddlewareInterface|class-string<MiddlewareInterface>>
     * } $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $prefix = $data['prefix'] ?? '';
        $name = $data['name'] ?? '';
        $parameters = collect($data['where'] ?? []);
        $middlewares = collect($data['middleware'] ?? []);

        return new self($prefix, $name, $parameters, $middlewares);
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->prefix;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @return Collection<string, string>
     */
    public function getWhere(): Collection
    {
        return $this->parameters;
    }

    /**
     * @return Collection<int, MiddlewareInterface|class-string<MiddlewareInterface>>
     */
    public function getMiddlewares(): Collection
    {
        return $this->middlewares;
    }
}