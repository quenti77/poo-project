<?php

namespace Tuto\Template;

use Closure;
use InvalidArgumentException;
use Tuto\Collections\Collection;

class FilterRegistry
{
    /**
     * @param Collection<string, Closure> $filters
     */
    public function __construct(private readonly Collection $filters)
    {
    }

    /**
     * @param string $name
     * @param Closure $filter
     * @return void
     */
    public function register(string $name, Closure $filter): void
    {
        $this->filters->offsetSet($name, $filter);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @param array $args
     * @return mixed
     */
    public function apply(string $name, mixed $value, array $args = []): mixed
    {
        if (!$this->filters->offsetExists($name)) {
            throw new InvalidArgumentException("Unknown filter: '{$name}'");
        }

        return ($this->filters[$name])($value, ...$args);
    }
}
