<?php

use Tuto\Base\Environment;
use Tuto\Container\DependencyInjectionContainer;
use Tuto\Container\Resolver;

if (!function_exists('container')) {
    /**
     * @template T of object
     * @param class-string<T>|string|null $item
     * @return (
     *      $item is null ? DependencyInjectionContainer : (
     *          $item is class-string ? T : mixed
     *      )
     * )
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

        if ($item === null) {
            return $container;
        }
        return $container->get($item);
    }
}

if (!function_exists('env')) {
    /**
     * @param string|null $key
     * @param bool|int|float|string|null $defaultValue
     * @return ($key is null ? Environment : bool|int|float|string|null)
     */
    function env(string|null $key = null, bool|int|float|string|null $defaultValue = null): bool|int|float|string|null|Environment
    {
        static $environment = new Environment();
        return $key === null ? $environment : $environment->get($key, $defaultValue);
    }
}
