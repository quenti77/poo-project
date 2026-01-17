<?php

namespace Tuto\Collections;

use InvalidArgumentException;

/**
 * @template TKey of array-key
 * @template-covariant TValue
 */
trait CollectionArrayable
{
    /** @use CollectionIterable<TKey, TValue> */
    use CollectionIterable;

    /** @var array<TKey, TValue> $items */
    protected array $items;

    /**
     * @param TKey $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return array_key_exists($offset, $this->items);
    }

    /**
     * @param TKey $offset
     * @return TValue
     * @throws InvalidArgumentException
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->offsetExists($offset)
            ? $this->items[$offset]
            : throw new InvalidArgumentException("'{$offset}' not in current collection");
    }

    /**
     * @param TKey $offset
     * @param TValue|null $defaultValue
     * @return TValue|null
     */
    public function get(mixed $offset, mixed $defaultValue = null): mixed
    {
        return $this->offsetExists($offset) ? $this->offsetGet($offset) : $defaultValue;
    }

    /**
     * @param TKey $offset
     * @param TValue $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!in_array($offset, $this->keys, true)) {
            $this->keys[] = $offset;
        }
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
