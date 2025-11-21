<?php

namespace Tuto\Container\Items;

use Tuto\Container\DependencyInjectionContainer;

class DependencyPrimitiveItem extends DependencyItem
{
    public function __construct(
        private readonly string $name,
        private readonly mixed $value,
    ) {
    }

    public function add(DependencyInjectionContainer $container): void
    {
        $container->addPrimitive($this->name, $this->value);
    }
}