<?php

use Tuto\Collections\Collection;
use Tuto\Container\DependencyInjectionContainer;
use Tuto\Container\Resolver;

if (!function_exists('collect')) {
    /**
     * @template TKey of array-key
     * @template-covariant TValue
     *
     * @param array<TKey, TValue> $items
     * @return Collection<TKey, TValue>
     */
    function collect(array $items = []): Collection
    {
        return new Collection($items);
    }
}

if (!function_exists('container')) {
    /**
     * @template T of object
     * @param class-string<T>|string|null $item
     * @return (
     *     $item is null ? DependencyInjectionContainer : (
     *         $item is class-string ? T : mixed
     *     )
     * )
     * @throws ReflectionException
     */
    function container(string|null $item = null): mixed
    {
        static $resolver = null;
        static $container = null;

        if ($resolver === null && $container === null) {
            $resolver = new Resolver();
            $container = new DependencyInjectionContainer();

            $resolver->withContainer($container);
            $container->withResolver($resolver);
        }

        return $item === null ? $container : $container->get($item);
    }
}
