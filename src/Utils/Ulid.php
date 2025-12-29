<?php

namespace Tuto\Utils;

use InvalidArgumentException;
use Random\RandomException;
use RuntimeException;

class Ulid
{
    /**
     * @param string $value
     */
    public function __construct(public readonly string $value)
    {
    }

    /**
     * @throws InvalidArgumentException
     * @throws RandomException
     * @throws RuntimeException
     */
    public static function next(): self
    {
        return new self(ulid());
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}