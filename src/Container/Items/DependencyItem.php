<?php

namespace Tuto\Container\Items;

abstract class DependencyItem
{
    /**
     * @param string $name
     * @param mixed $value
     * @return DependencyPrimitiveItem
     */
    public static function primitive(string $name, mixed $value): DependencyPrimitiveItem
    {
        return new DependencyPrimitiveItem($name, $value);
    }

    /**
     * @param string $name
     * @param callable $factory
     * @return DependencyFactoryItem
     */
    public static function single(string $name, callable $factory): DependencyFactoryItem
    {
        return new DependencyFactoryItem($name, $factory);
    }

    /**
     * @param string $name
     * @param callable $factory
     * @return DependencyFactoryItem
     */
    public static function factory(string $name, callable $factory): DependencyFactoryItem
    {
        return new DependencyFactoryItem($name, $factory, false);
    }

    /**
     * @param class-string $interface
     * @param class-string $concrete
     * @return DependencyInterfaceItem
     */
    public static function concrete(string $interface, string $concrete): DependencyInterfaceItem
    {
        return new DependencyInterfaceItem($interface, $concrete);
    }

    abstract public function getKeyName(): string;
}
