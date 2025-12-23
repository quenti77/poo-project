<?php

namespace Tuto\Container;

use InvalidArgumentException;
use ReflectionException;
use RuntimeException;
use Tuto\Collections\Collection;
use Tuto\Container\Items\DependencyFactoryItem;
use Tuto\Container\Items\DependencyInterfaceItem;
use Tuto\Container\Items\DependencyItem;
use Tuto\Container\Items\DependencyPrimitiveItem;

class DependencyInjectionContainer
{
    /** @var Collection<string, DependencyItem> $dependencies */
    private Collection $dependencies;

    /** @var Collection<string, mixed> $instances */
    private Collection $instances;

    /**
     * @param Resolver|null $resolver
     */
    public function __construct(private Resolver|null $resolver = null)
    {
        $this->dependencies = collect();
        $this->instances = collect();
    }

    /**
     * @param Resolver $resolver
     * @return void
     */
    public function withResolver(Resolver $resolver): void
    {
        $this->resolver = $resolver;
    }

    /**
     * @return Resolver
     */
    public function resolver(): Resolver
    {
        if ($this->resolver === null) {
            throw new RuntimeException("Resolver must be set before used");
        }
        return $this->resolver;
    }

    /**
     * @param DependencyItem $dependencyItem
     * @return void
     */
    public function addDependencyItem(DependencyItem $dependencyItem): void
    {
        $this->dependencies[$dependencyItem->getKeyName()] = $dependencyItem;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws ReflectionException
     */
    public function get(string $name): mixed
    {
        // We have already got an instance
        if ($this->instances->offsetExists($name)) {
            return $this->instances[$name];
        }

        // If we do not have dependency item, we need to create an instance from name
        if (!$this->dependencies->offsetExists($name)) {
            $instance = $this->resolver()->instantiate($name);
            if ($instance) {
                return $this->instances[$name] = $instance;
            }

            throw new InvalidArgumentException("Can not be resolve the unknown '{$name}'");
        }

        $dependency = $this->dependencies[$name];
        if ($dependency instanceof DependencyPrimitiveItem) {
            return $dependency->getValue();
        }

        if ($dependency instanceof DependencyInterfaceItem) {
            $this->instances[$name] = $this->get($dependency->getConcrete());
        }

        if ($dependency instanceof DependencyFactoryItem) {
            $instance = $this->callFactory($dependency->getFactory());
            if ($dependency->isSingleInstance()) {
                $this->instances[$name] = $instance;
            }
            return $instance;
        }

        $dep = $dependency::class;
        throw new InvalidArgumentException("Can not be resolve the dependency item '{$dep}'");
    }

    private function callFactory(callable $factory): mixed
    {
        return $factory($this);
    }
}
