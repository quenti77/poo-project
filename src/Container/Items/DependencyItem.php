<?php

namespace Tuto\Container\Items;

abstract class DependencyItem
{
    /**
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public static function primitive(string $name, mixed $value): static
    {
        return new DependencyPrimitiveItem($name, $value);
    }

    public static function factory(string $name, callable $factory, bool $singleInstance = true): static
    {
        return new DependencyFactoryItem($name, $factory, $singleInstance);
    }

    /**
     * @param class-string $interface
     * @param class-string $concrete
     * @return DependencyInterfaceItem
     */
    public static function interface(string $interface, string $concrete): static
    {
        return new DependencyInterfaceItem($interface, $concrete);
    }

    abstract public function getKeyName(): string;
}