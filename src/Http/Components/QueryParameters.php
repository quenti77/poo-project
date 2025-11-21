<?php

namespace Tuto\Http\Components;

use Tuto\Collection\Collection;

class QueryParameters extends Collection
{
    public static function fromGlobals(): self
    {
        return new self($_GET);
    }
}