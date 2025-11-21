<?php

namespace Tuto\Container;

use InvalidArgumentException;
use ReflectionClass;
use ReflectionException;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;
use RuntimeException;

class Resolver
{
    public function __construct(private DependencyInjectionContainer|null $container = null)
    {
    }

    public function withContainer(DependencyInjectionContainer $container): void
    {
        $this->container = $container;
    }

    /**
     * @param string $class
     * @return mixed
     * @throws ReflectionException
     */
    public function instantiate(string $class): mixed
    {
        $reflectionClass = new ReflectionClass($class);
        $reflectionConstructor = $reflectionClass->getConstructor();

        if ($reflectionConstructor === null) {
            return $reflectionClass->newInstanceWithoutConstructor();
        }

        $constructorParameters = $this->getParameters($reflectionConstructor->getParameters());
        return $reflectionClass->newInstanceArgs($constructorParameters);
    }

    /**
     * @param ReflectionParameter[] $parameters
     * @param array $context
     * @return array
     * @throws ReflectionException
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
     * @param ReflectionParameter $parameter
     * @return mixed
     * @throws ReflectionException
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
            default => throw new RuntimeException("Resolver cannot be resolved '{$type::class}'"),
        };

        return $this->resolveParameterTypes($parameter, $typeArgs);
    }

    /**
     * @param ReflectionNamedType[] $types
     * @return mixed
     * @throws ReflectionException
     */
    private function resolveParameterTypes(ReflectionParameter $parameter, array $types): mixed
    {
        if ($this->container === null) {
            throw new RuntimeException("Container must be set before used");
        }

        $allowNull = false;
        foreach ($types as $type) {
            try {
                return $this->container->get($type->getName());
            } catch (InvalidArgumentException) {
                if ($type->allowsNull()) {
                    $allowNull = true;
                }
            }
        }

        try {
            return $this->container->get($parameter->getName());
        } catch (InvalidArgumentException) {
            if ($allowNull) {
                return null;
            }
        }

        throw new RuntimeException("Cannot be resolve '{$parameter->getName()}' in '{$parameter->getDeclaringClass()?->getName()}'");
    }
}