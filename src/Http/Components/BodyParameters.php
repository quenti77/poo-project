<?php

namespace Tuto\Http\Components;

use Tuto\Collection\Collection;

class BodyParameters extends Collection
{
    public static function fromGlobals(): self
    {
        return new self($_POST);
    }
}