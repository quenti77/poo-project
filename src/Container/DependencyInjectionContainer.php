<?php

namespace Tuto\Container;

use InvalidArgumentException;
use ReflectionException;
use RuntimeException;

class DependencyInjectionContainer
{
    /** @var array<string, mixed> */
    private array $primitives = [];

    /** @var array<class-string, class-string> */
    private array $interfaces = [];

    /** @var array<string, callable> */
    private array $factories = [];

    /** @var array<string, mixed> */
    private array $instances = [];

    public function __construct(private Resolver|null $resolver = null)
    {
    }

    public function resolver(): Resolver
    {
        if ($this->resolver === null) {
            throw new RuntimeException("Resolver must be set before used");
        }
        return $this->resolver;
    }

    public function withResolver(Resolver $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function addPrimitive(string $name, mixed $value): void
    {
        $this->primitives[$name] = $value;
    }

    /**
     * @param class-string $interface
     * @param class-string $concrete
     * @return void
     * @throws InvalidArgumentException
     */
    public function addInterface(string $interface, string $concrete): void
    {
        if (!interface_exists($interface)) {
            throw new InvalidArgumentException("Interface '{$interface}' does not exist");
        }
        if (!class_exists($concrete)) {
            throw new InvalidArgumentException("Class '{$concrete}' does not exist");
        }
        $this->interfaces[$interface] = $concrete;
    }

    public function addFactory(string $name, callable $value): void
    {
        $this->factories[$name] = $value;
    }

    /**
     * @param string $name
     * @return mixed
     * @throws RuntimeException
     * @throws ReflectionException
     */
    public function get(string $name): mixed
    {
        if (array_key_exists($name, $this->primitives)) {
            return $this->primitives[$name];
        }
        if (array_key_exists($name, $this->instances)) {
            return $this->instances[$name];
        }
        if (array_key_exists($name, $this->interfaces)) {
            return $this->instances[$name] = $this->get($this->interfaces[$name]);
        }
        if (array_key_exists($name, $this->factories)) {
            return $this->instances[$name] = $this->callFactory($this->factories[$name]);
        }

        $instance = $this->resolver()->instantiate($name);
        if ($instance) {
            return $this->instances[$name] = $instance;
        }

        throw new InvalidArgumentException("Cannot be resolve the unknown '{$name}'");
    }

    private function callFactory(callable $factory): mixed
    {
        return $factory($this);
    }
}