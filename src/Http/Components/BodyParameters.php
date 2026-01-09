<?php

namespace Tuto\Http\Components;

use Tuto\Collections\Collection;

/**
 * @implements Collection<int|string, mixed>
 */
class BodyParameters extends Collection
{
    public static function fromGlobals(): self
    {
        return new self($_POST);
    }
}
