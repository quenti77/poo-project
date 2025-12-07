<?php

namespace Tuto\Collection;

use InvalidArgumentException;

/**
 * @template TKey
 * @template TVal
 */
trait CollectionArrayable
{
    /** @uses CollectionIterable<TKey> */
    use CollectionIterable;

    /** @var array<TKey, TVal> */
    protected array $items;

    /**
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param TKey|null $offset
     * @return TVal|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? throw new InvalidArgumentException("'{$offset}' not in current collection");
    }

    /**
     * @param TKey $offset
     * @param TVal $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->keys[] = $offset;
        $this->items[$offset] = $value;
    }

    /**
     * @param TKey $offset
     * @return void
     */
    public function offsetUnset(mixed $offset): void
    {
        $this->keys = array_filter($this->keys, static fn (string|int $key) => $key !== $offset);
        if (!$this->valid()) {
            $this->counter = count($this->keys) - 1;
        }

        unset($this->items[$offset]);
    }

    public function count(): int
    {
        return count($this->items);
    }
}