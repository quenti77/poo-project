<?php

namespace Tuto\Container\Items;

abstract class DependencyItem
{
    public static function primitive(): DependencyPrimitiveItem
    {

    }

    abstract public function getKeyName(): string;
}