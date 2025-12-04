<?php

use Tuto\Collections\Collection;

if (!function_exists('collect')) {
    /**
     * @template TKey of array-key
     * @template-covariant TValue
     *
     * @param array<TKey, TValue> $items
     * @return Collection<TKey, TValue>
     */
    function collect(array $items = []): Collection
    {
        return new Collection($items);
    }
}
