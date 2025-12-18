<?php

namespace Tuto\Http\Components;

use Tuto\Collections\Collection;

/**
 * @implements Collection<int|string, mixed>
 */
class FileParameters extends Collection
{
    public static function fromGlobals(): self
    {
        return new self($_FILES);
    }
}