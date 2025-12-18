<?php

namespace Tuto\Container\Items;

class DependencyFactoryItem extends DependencyItem
{
    /** @var callable $factory */
    private $factory;

    /**
     * @param string $name
     * @param callable $factory
     * @param bool $singleInstance
     */
    public function __construct(
        private readonly string $name,
        callable $factory,
        private readonly bool $singleInstance = true,
    )
    {
        $this->factory = $factory;
    }

    public function getKeyName(): string
    {
        return $this->name;
    }

    public function isSingleInstance(): bool
    {
        return $this->singleInstance;
    }

    public function getFactory(): callable
    {
        return $this->factory;
    }
}