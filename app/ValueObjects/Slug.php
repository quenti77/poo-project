<?php

namespace App\ValueObjects;

use InvalidArgumentException;
use Tuto\Utils\Str;

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
     * @param string $content
     * @return self
     */
    public static function fromString(string $content): self
    {
        return new self(Str::slug($content));
    }

    /**
     * @param string $slug
     * @return bool
     */
    public static function verify(string $slug): bool
    {
        return preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->value;
    }
}