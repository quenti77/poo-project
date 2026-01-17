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
     * @return TValue|null
     */
    public function shift(): mixed
    {
        $value = array_shift($this->items);

        $this->counter = min(count($this->items), $this->counter);
        $this->keys = array_keys($this->items);

        return $value;
    }

    /**
     * @param TValue $item
     * @return self
     */
    public function unshift(mixed $item): self
    {
        array_unshift($this->items, $item);

        $this->keys = array_keys($this->items);
        return $this;
    }

    /**
     * @return TValue|null
     */
    public function pop(): mixed
    {
        $value = array_pop($this->items);

        $this->counter = min(count($this->items), $this->counter);
        $this->keys = array_keys($this->items);

        return $value;
    }

    /**
     * @template-covariant TNewValue
     *
     * @param callable(TKey $key, TValue $value): TNewValue $handler
     * @param bool $preserveKeys
     * @return self<TKey, TNewValue>
     */
    public function map(callable $handler, bool $preserveKeys = true): self
    {
        $newArray = array_map(fn(string|int $key) => $handler($key, $this->items[$key]), $this->keys);
        if ($preserveKeys) {
            $newArray = array_combine($this->keys, $newArray);
        }

        return new self($newArray);
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

    public function merge(array|self $items): self
    {
        if ($items instanceof self) {
            $items = $items->all();
        }
        return new self(array_merge($this->items, $items));
    }

    /**
     * @return self<TKey, TValue>
     */
    public function reverse(): self
    {
        return new self(array_reverse($this->items));
    }

    /**
     * @return Collection<int, TValue>
     */
    public function values(): self
    {
        return collect(array_values($this->items));
    }

    /**
     * @return Collection<array{0: TKey, 1: TValue}>
     */
    public function entries(): self
    {
        return $this->map(static fn (int|string $key, mixed $value) => [$key, $value], false);
    }

    /**
     * @param TValue ...$values
     * @return bool
     */
    public function has(mixed ...$values): bool
    {
        $search = $this->values()->all();
        return array_any($values, static fn($value) => in_array($value, $search, true));
    }

    /**
     * @return array<TKey, TValue>
     */
    public function all(): array
    {
        $result = [];
        foreach ($this as $key => $value) {
            if ($value instanceof self) {
                $value = $value->all();
            }
            $result[$key] = $value;
        }
        return $result;
    }

    /**
     * @return TValue
     */
    public function first(): mixed
    {
        if ($this->isEmpty()) {
            return null;
        }
        return $this[$this->keys[0]];
    }

    /**
     * @param string $delimiter
     * @return string
     */
    public function join(string $delimiter = ''): string
    {
        return implode($delimiter, $this->items);
    }

    /**
     * @return bool
     */
    public function isEmpty(): bool
    {
        return $this->count() === 0;
    }
}
