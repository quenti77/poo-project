<?php

namespace Tuto\Http\Components;

use Tuto\Collections\Collection;

/**
 * @implements Collection<string, string>
 */
class Headers extends Collection
{
    public static function fromGlobals(): self
    {
        return new self(getallheaders() ?: []);
    }
}