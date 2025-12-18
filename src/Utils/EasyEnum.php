<?php

namespace Tuto\Utils;

use Tuto\Collections\Collection;


trait EasyEnum
{
    /**
     * @return Collection<int|string, int|string>
     */
    public function entries(): Collection
    {
        $entries = collect();

        foreach (self::cases() as $case) {
            $entries[$case->name] = $case->value;
        }

        return $entries;
    }

    /**
     * @return Collection<int, int|string>
     */
    public function keys(): Collection
    {
        return $this->entries()->keys();
    }

    public function hasKeys(int|string ...$keys): bool
    {
        return collect($keys)->has($this->name);
    }

    /**
     * @return Collection<int, int|string>
     */
    public function values(): Collection
    {
        return $this->entries()->values();
    }

    /**
     * @param int|string ...$values
     * @return bool
     */
    public function has(int|string ...$values): bool
    {
        return collect($values)->has($this->value);
    }
}
