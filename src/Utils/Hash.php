<?php

namespace Tuto\Utils;

class Hash
{
    /**
     * @param string $password
     * @param int $cost
     * @param string $passwordType
     * @return string
     */
    public static function make(string $password, int $cost = 12, string $passwordType = PASSWORD_DEFAULT): string
    {
        return password_hash($password, $passwordType, ['cost' => $cost]);
    }
}