<?php

namespace Tuto\Utils;

use Tuto\Collections\Collection;


trait EasyEnum
{
    /**
     * @return Collection<int|string, int|string>
     */
    public static function entries(): Collection
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
    public static function keys(): Collection
    {
        return self::entries()->keys();
    }

    public function hasKeys(int|string ...$keys): bool
    {
        return collect($keys)->has($this->name);
    }

    /**
     * @return Collection<int, int|string>
     */
    public static function values(): Collection
    {
        return self::entries()->values();
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
