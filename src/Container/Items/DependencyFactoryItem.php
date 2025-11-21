<?php

namespace Tuto\Container\Items;

use Tuto\Container\DependencyInjectionContainer;

class DependencyFactoryItem extends DependencyItem
{
    /** @var callable $factory */
    private $factory;

    public function __construct(
        private readonly string $name,
        callable $factory
    ) {
        $this->factory = $factory;
    }

    public function add(DependencyInjectionContainer $container): void
    {
        $container->addFactory($this->name, $this->factory);
    }
}