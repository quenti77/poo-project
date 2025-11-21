<?php

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
