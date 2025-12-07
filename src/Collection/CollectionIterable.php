<?php

namespace Tuto\Collection;

use RuntimeException;

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
        if (!method_exists($this, 'offsetGet')) {
            throw new RuntimeException("This trait must be used with class who implements the ArrayAccess interface");
        }
        return $this->offsetGet($this->key());
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