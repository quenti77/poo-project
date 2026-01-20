<?php

namespace Tuto\Utils;

class Str
{
    /**
     * @param string $content
     * @return string
     */
    public static function slug(string $content): string
    {
        return $content
                |> mb_strtolower(...)
                |> (static fn (string $s) => preg_replace('/[^a-z0-9]+/', '-', $s))
                |> (static fn (string $s) => trim($s, '-') ?: 'n-a');
    }
}