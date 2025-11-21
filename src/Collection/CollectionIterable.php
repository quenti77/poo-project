<?php

namespace Tuto\Collection;

/**
 * @template TKey
 * @template TVal
 */
trait CollectionIterable
{
    private int $counter;

    /** @var array<int, TKey> $keys */
    private array $keys;

    /**
     * @return TVal
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