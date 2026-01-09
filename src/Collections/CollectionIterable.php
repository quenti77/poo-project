<?php

namespace Tuto\Collections;

use RuntimeException;

/**
 * @template TKey of array-key
 * @template-covariant TValue
 */
trait CollectionIterable
{
    /** @var int $counter */
    protected int $counter;

    /** @var array<int, TKey> $keys */
    protected array $keys;

    /**
     * @return Collection<int, TKey>
     */
    public function keys(): Collection
    {
        return collect($this->keys);
    }

    /**
     * @param TKey ...$keys
     * @return bool
     */
    public function hasKeys(mixed ...$keys): bool
    {
        return array_any($keys, fn($key) => in_array($key, $this->keys, true));
    }

    /**
     * @return TValue
     */
    public function current(): mixed
    {
        // Check if trait can get a value by a key
        if (!method_exists($this, 'offsetGet')) {
            throw new RuntimeException("This trait must be used with class who implements the ArrayAccess interface");
        }
        return $this->offsetGet($this->key());
    }

    public function next(): void
    {
        $this->counter += 1;
    }

    /**
     * @return TKey
     */
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
