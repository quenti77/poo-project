<?php

namespace Tuto\Routing;

use Tuto\Collections\Collection;
use Tuto\Http\Requests\Uri;

class RouteGroup
{
    /**
     * @param string $prefix
     * @param string $name
     */
    public function __construct(
        private string $prefix,
        private string $name,
        private readonly Collection $parameters,
    ) {
        $this->prefix = Uri::trimPath($this->prefix);
        $this->name = trim($this->name);
    }

    /**
     * @param array{prefix?: string, name?: string, where?: array<string, string>} $data
     * @return self
     */
    public static function fromArray(array $data): self
    {
        $prefix = $data['prefix'] ?? '';
        $name = $data['name'] ?? '';
        $parameters = collect($data['where'] ?? []);

        return new self($prefix, $name, $parameters);
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
}