<?php

namespace Tuto\Container;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

class Resolver
{
    /**
     * @param DependencyInjectionContainer|null $container
     */
    public function __construct(private DependencyInjectionContainer|null $container = null)
    {
    }

    public function container(): DependencyInjectionContainer
    {
        if ($this->container === null) {
            throw new RuntimeException("Container must be set before used");
        }
        return $this->container;
    }

    /**
     * @param DependencyInjectionContainer $container
     * @return void
     */
    public function withContainer(DependencyInjectionContainer $container): void
    {
        $this->container = $container;
    }

    /**
     * @template TInstance
     * @param class-string<TInstance> $class
     * @return TInstance
     * @throws ReflectionException
     */
    public function instantiate(string $class): mixed
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionConstructor = $reflectionClass->getConstructor();

        // If the class does not have any constructor
        if ($reflectionConstructor === null) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        // Else, get parameters into array
        $constructorParameters = $this->getParameters($reflectionConstructor->getParameters());
        return $reflectionClass->newInstanceArgs($constructorParameters);
    }

    /**
     * Get value to use in parameter in method or function
     * Eg: test(string $name, Foo $foo): void {}
     * -> $context = ['name' => 'John']
     * -> Foo => resolve with DIC
     *
     * @param ReflectionParameter[] $parameters
     * @param array<string, mixed> $context
     * @return array<int, mixed>
     */
    public function getParameters(array $parameters, array $context = []): array
    {
        $result = [];

        foreach ($parameters as $parameter) {
            $parameterName = $parameter->getName();
            $result[] = array_key_exists($parameterName, $context)
                ? $context[$parameterName]
                : $this->resolveParameter($parameter);
        }

        return $result;
    }

    /**
     * @param array{0: class-string, 1: string} $handler
     * @param array<string, mixed> $context
     * @return mixed
     * @throws ReflectionException
     */
    public function resolveArray(array $handler, array $context = []): mixed
    {
        // Handler array must have 2 elements [class-string, method-string]
        if (count($handler) !== 2) {
            throw new InvalidArgumentException("Handler array must be only 2 items [class-string, method-string]");
        }

        [$className, $methodName] = $handler;
        $instance = $this->instantiate($className);

        $reflectionMethod = new ReflectionMethod($instance, $methodName);
        $parameters = $this->getParameters($reflectionMethod->getParameters(), $context);

        return $reflectionMethod->invokeArgs($instance, $parameters);
    }

    /**
     * @param callable $handler
     * @param array<string, mixed> $context
     * @return mixed
     * @throws ReflectionException
     */
    public function resolveCallable(callable $handler, array $context = []): mixed
    {
        $reflectionCallable = new ReflectionFunction($handler);
        $parameters = $this->getParameters($reflectionCallable->getParameters(), $context);

        return $reflectionCallable->invokeArgs($parameters);
    }

    /**
     * @param ReflectionParameter $parameter
     * @return mixed
     */
    private function resolveParameter(ReflectionParameter $parameter): mixed
    {
        $type = $parameter->getType();
        if ($type === null) {
            return null;
        }

        $typeArgs = match (true) {
            $type instanceof ReflectionNamedType => [$type],
            $type instanceof ReflectionUnionType => array_filter(
                $type->getTypes(),
                static fn (ReflectionNamedType|ReflectionIntersectionType $type) => $type instanceof ReflectionNamedType,
            ),
            default => throw new RuntimeException("Resolver can not be resolved '{$type->getName()}'"),
        };

        return $this->resolveParameterTypes($parameter, $typeArgs);
    }

    /**
     * @param ReflectionParameter $parameter
     * @param ReflectionNamedType[] $types
     * @return mixed
     */
    private function resolveParameterTypes(ReflectionParameter $parameter, array $types): mixed
    {
        $allowNull = false;
        foreach ($types as $type) {
            try {
                return $this->container()->get($type->getName());
            } catch (InvalidArgumentException) {
                if ($type->allowsNull()) {
                    $allowNull = true;
                }
            }
        }

        try {
            return $this->container()->get($parameter->getName());
        } catch (InvalidArgumentException) {
            if ($allowNull) {
                return null;
            }
        }

        throw new RuntimeException("Can not be resolve '{$parameter->getName()}' in '{$parameter->getDeclaringClass()}'");
    }
}
