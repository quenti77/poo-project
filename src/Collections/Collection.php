<?php

namespace Tuto\Collections;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * @template TKey of array-key
 * @template-covariant TValue
 *
 * @implements ArrayAccess<TKey, TValue>
 * @implements Countable
 * @implements Iterator<TKey, TValue>
 */
class Collection implements ArrayAccess, Countable, Iterator
{
    /** @use CollectionArrayable<TKey, TValue> */
    use CollectionArrayable;

    /**
     * @param array<TKey, TValue> $items
     */
    public function __construct(array $items = [])
    {
        $this->items = $items;
        $this->counter = 0;
        $this->keys = array_keys($this->items);
    }

    /**
     * @param TValue ...$values
     * @return void
     */
    public function push(mixed ...$values): void
    {
        foreach ($values as $value) {
            $this->items[] = $value;
        }
        $this->keys = array_keys($this->items);
    }

    /**
     * @template-covariant TNewValue
     *
     * @param callable(TKey $key, TValue $value): TNewValue $handler
     * @return self<TKey, TNewValue>
     */
    public function map(callable $handler): self
    {
        return new self(
            array_map(static fn(string|int $key) => $handler($key, $this->items[$key]), $this->keys),
        );
    }

    /**
     * @param callable(TKey $key, TValue $value): bool $handler
     * @return self<TKey, TValue>
     */
    public function filter(callable $handler): self
    {
        return new self(
            array_filter(
                $this->items,
                static fn(mixed $value, string|int $key) => $handler($key, $value),
                ARRAY_FILTER_USE_BOTH,
            ),
        );
    }

    /**
     * @template-covariant TReduceValue
     *
     * @param callable(TKey $key, TValue $value, TReduceValue $acc): TReduceValue $handler
     * @param TReduceValue|null $initialValue
     * @return TReduceValue
     */
    public function reduce(callable $handler, mixed $initialValue = null): mixed
    {
        $result = $initialValue;
        foreach ($this->items as $key => $value) {
            $result = $handler($key, $value, $result);
        }
        return $result;
    }

    /**
     * @param callable(TKey $key, TValue $value): bool $handler
     * @return TValue|null
     */
    public function find(callable $handler): mixed
    {
        return array_find($this->items, static fn (mixed $value, string|int $key) => $handler($key, $value));
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return self<TKey, TValue>
     */
    public function slice(int $offset, int|null $length = null): self
    {
        return new self(array_slice($this->items, $offset, $length));
    }

    /**
     * @return self<TKey, TValue>
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    /**
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        return $this->items;
    }
}