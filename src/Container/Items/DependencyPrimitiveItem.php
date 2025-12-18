<?php

namespace Tuto\Container\Items;

class DependencyPrimitiveItem extends DependencyItem
{
    /**
     * @param string $name
     * @param mixed $value
     */
    public function __construct(
        private readonly string $name,
        private readonly mixed $value,
    ) {
    }

    public function getKeyName(): string
    {
        return $this->name;
    }

    public function getValue(): mixed
    {
        return $this->value;
    }
}