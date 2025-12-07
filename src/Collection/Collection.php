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
     * @param TVal $value
     * @return void
     */
    public function push(mixed $value): void
    {
        $this->items[] = $value;
        $this->keys = array_keys($this->items);
    }

    /**
     * @template-covariant TNewVal
     *
     * @param callable(TKey $key, TVal $value): TNewVal $handler
     * @return self<TKey, TNewVal>
     */
    public function map(callable $handler): self
    {
        $newItems = array_map(
            fn (string|int $key) => $handler($key, $this->items[$key]),
            $this->keys
        );
        return new self($newItems);
    }

    /**
     * @param callable(TKey $key, TVal $value): bool $handler
     * @return self<TKey, TVal>
     */
    public function filter(callable $handler): self
    {
        $newItems = array_filter(
            $this->items,
            static fn (mixed $value, string|int $key) => $handler($key, $value),
            ARRAY_FILTER_USE_BOTH,
        );
        return new self($newItems);
    }

    /**
     * @template-covariant TReduceVal
     *
     * @param callable(TKey $key, TVal $value, TReduceVal $acc): TReduceVal $handler
     * @param TReduceVal|null $initialValue
     * @return TReduceVal
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
     * @param callable(TKey $key, TVal $value): bool $handler
     * @return TVal|null
     */
    public function find(callable $handler): mixed
    {
        return array_find($this->items, static fn (mixed $value, string|int $key) => $handler($key, $value));
    }

    /**
     * @param int $offset
     * @param int|null $length
     * @return Collection<TKey, TVal>
     */
    public function slice(int $offset, int|null $length = null): Collection
    {
        return collect(array_slice($this->items, $offset, $length));
    }

    /**
     * @return array<TKey, TVal>
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * @return self<TKey, TVal>
     */
    public function reverse(): self
    {
        return collect(array_reverse($this->items));
    }
}
