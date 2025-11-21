<?php

namespace Tuto\Collection;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * @template TKey
 * @template TVal
 */
class Collection implements ArrayAccess, Countable, Iterator
{
    /** @uses CollectionArrayable<TVal> */
    use CollectionArrayable;

    /**
     * @param array<TKey, TVal> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->counter = 0;
        $this->keys = array_keys($this->items);
    }

    /**
     * @param T $value
     * @return void
     */
    public function push(mixed $value): void
    {
        $this->items[] = $value;
        $this->keys = array_keys($this->items);
    }

    public function map(callable $handler): self
    {
        return new self(array_map($handler, $this->items));
    }

    public function filter(callable $handler): self
    {
        return new self(array_filter($this->items, $handler));
    }

    public function reduce(callable $handler, mixed $initialValue = null): mixed
    {
        return array_reduce($this->items, $handler, $initialValue);
    }

    /**
     * @param callable $handler
     * @return TVal|null
     */
    public function find(callable $handler): mixed
    {
        return array_find($this->items, $handler);
    }
}