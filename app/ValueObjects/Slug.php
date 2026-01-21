<?php

namespace App\ValueObjects;

use InvalidArgumentException;

class Slug
{
    /**
     * @param string $value
     */
    public function __construct(public readonly string $value)
    {
        if (!self::verify($this->value)) {
            throw new InvalidArgumentException("Slug use an invalid format");
        }
    }

    /**
     * @param string $slug
     * @return bool
     */
    public static function verify(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }
}