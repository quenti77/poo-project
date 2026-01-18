<?php

namespace Tuto\Template\Filters;

use Closure;
use RuntimeException;

class FilterRegistry
{
    /** @var array<string, Closure> $filters */
    private array $filters = [];

    /**
     * @param string $name
     * @param Closure $filter
     * @return void
     */
    public function add(string $name, Closure $filter): void
    {
        $this->filters[$name] = $filter;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function has(string $name): bool
    {
        return isset($this->filters[$name]);
    }

    /**
     * @param string $name
     * @param mixed ...$args
     * @return mixed
     */
    public function apply(string $name, mixed ...$args): mixed
    {
        if ($this->has($name) === false) {
            throw new RuntimeException("Unknown filter: {$name}");
        }

        return ($this->filters[$name])(...$args);
    }
}
