<?php

namespace Tuto\Utils;

class File
{
    /**
     * @param string $path
     * @return string
     */
    public static function absolute(string $path): string
    {
        return str_starts_with($path, '/') ? $path : ROOT . "/{$path}";
    }
}
