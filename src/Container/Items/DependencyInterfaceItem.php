<?php

namespace Tuto\Container\Items;

use InvalidArgumentException;
use Tuto\Container\DependencyInjectionContainer;

class DependencyInterfaceItem extends DependencyItem
{
    /**
     * @param class-string $interface
     * @param class-string $concrete
     */
    public function __construct(
        private readonly string $interface,
        private readonly string $concrete,
    ) {
        if (!interface_exists($this->interface)) {
            throw new InvalidArgumentException("Interface '{$this->interface}' does not exist");
        }
        if (!class_exists($this->concrete)) {
            throw new InvalidArgumentException("Class '{$this->concrete}' does not exist");
        }
    }

    /**
     * @return class-string
     */
    public function getKeyName(): string
    {
        return $this->interface;
    }

    /**
     * @return class-string
     */
    public function getConcrete(): string
    {
        return $this->concrete;
    }
}