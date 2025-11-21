<?php

namespace Tuto\Collection;

/**
 * @template T
 */
trait CollectionArrayable
{
    /** @uses CollectionIterable<T> */
    use CollectionIterable;

    /** @var array<string|int, T> */
    protected array $items;

    /**
     * @param string|int $offset
     * @return bool
     */
    public function offsetExists(mixed $offset): bool
    {
        return isset($this->items[$offset]);
    }

    /**
     * @param string|null $offset
     * @return T|null
     */
    public function offsetGet(mixed $offset): mixed
    {
        return $this->items[$offset] ?? null;
    }

    /**
     * @param string|int $offset
     * @param T $value
     * @return void
     */
    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->keys[] = $offset;
        $this->items[$offset] = $value;
    }

    /**
     * @param string|int $offset
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