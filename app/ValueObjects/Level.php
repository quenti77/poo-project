<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class Level
{
    /**
     * @param int $value
     */
    public function __construct(public readonly int $value)
    {
        if ($this->value < 0) {
            throw new InvalidArgumentException('Level must be greater than or equals to 0');
        }
    }

    /**
     * @param Level|int $minLevel
     * @return bool
     */
    public function canAccess(self|int $minLevel): bool
    {
        if (is_int($minLevel)) {
            $minLevel = new Level($minLevel);
        }
        return $this->value >= $minLevel->value;
    }
}