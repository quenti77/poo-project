<?php

namespace Tuto\Collection;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * @template T
 */
class Collection implements ArrayAccess, Countable, Iterator
{
    /** @uses CollectionArrayable<T> */
    use CollectionArrayable;

    /**
     * @param array<string|int, T> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->counter = 0;
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
}