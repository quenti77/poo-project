<?php

namespace App\ValueObjects;

use Exception;

class Ulid
{
    public function __construct(public readonly string $value)
    {
    }

    /**
     * @throws Exception
     */
    public static function next(): self
    {
        return new self(ulid());
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
