<?php

namespace Tuto\Collection;

/**
 * @template T
 */
trait CollectionIterable
{
    private int $counter;

    /** @var array<int, string|int> $keys */
    private array $keys;

    /**
     * @return T
     */
    public function current(): mixed
    {
        return $this[$this->counter];
    }

    public function next(): void
    {
        $this->counter += 1;
    }

    public function key(): mixed
    {
        return $this->keys[$this->counter];
    }

    public function valid(): bool
    {
        return isset($this->keys[$this->counter]);
    }

    public function rewind(): void
    {
        $this->counter = 0;
    }
}