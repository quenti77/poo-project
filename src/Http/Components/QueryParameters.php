<?php

namespace Tuto\Http\Components;

use Tuto\Collections\Collection;

/**
 * @implements Collection<int|string, mixed>
 */
class QueryParameters extends Collection
{
    public static function fromGlobals(): self
    {
        return new self($_GET);
    }
}
