<?php

namespace Tuto\Container\Items;

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
    }

    /**
     * @param DependencyInjectionContainer $container
     * @return void
     */
    public function add(DependencyInjectionContainer $container): void
    {
        $container->addInterface($this->interface, $this->concrete);
    }
}