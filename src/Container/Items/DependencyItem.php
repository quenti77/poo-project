<?php

namespace Tuto\Container\Items;

use Tuto\Container\DependencyInjectionContainer;

abstract class DependencyItem
{
    abstract public function add(DependencyInjectionContainer $container): void;

    public static function primitive(string $name, mixed $value): static
    {
        return new DependencyPrimitiveItem($name, $value);
    }

    public static function factory(string $name, callable $factory): static
    {
        return new DependencyFactoryItem($name, $factory);
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
}