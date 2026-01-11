<?php

namespace Tuto\Cache;

use ReflectionException;
use RuntimeException;
use Tuto\Cache\Drivers\FileCache;
use Tuto\Cache\Drivers\RedisCache;
use Tuto\Container\DependencyInjectionContainer;
use Tuto\Database\Redis\RedisConnection;
use Tuto\Utils\CurrentTime;
use Tuto\Utils\File;

class CacheFactory
{
    /**
     * @param DependencyInjectionContainer $container
     * @return CacheInterface
     * @throws ReflectionException
     */
    public static function make(DependencyInjectionContainer $container): CacheInterface
    {
        $driver = $container->getWithoutError('cache.driver', 'file');

        return match ($driver) {
            'file' => self::makeFileCache($container),
            'redis' => self::makeRedisCache($container),
            default => throw new RuntimeException("Unsupported cache driver: {$driver}"),
        };
    }

    /**
     * @param DependencyInjectionContainer $container
     * @return FileCache
     * @throws ReflectionException
     */
    private static function makeFileCache(DependencyInjectionContainer $container): FileCache
    {
        $cachePath = $container->getWithoutError('cache.file.path', 'storage/framework/cache');
        $cachePath = File::absolute($cachePath);

        $prefix = $container->getWithoutError('cache.prefix', 'cache');

        return new FileCache(
            currentTime: $container->get(CurrentTime::class),
            cachePath: $cachePath,
            prefix: $prefix,
        );
    }

    /**
     * @param DependencyInjectionContainer $container
     * @return RedisCache
     * @throws ReflectionException
     */
    private static function makeRedisCache(DependencyInjectionContainer $container): RedisCache
    {
        $prefix = $container->getWithoutError('cache.prefix', 'cache');

        return new RedisCache(
            redis: $container->get(RedisConnection::class),
            currentTime: $container->get(CurrentTime::class),
            prefix: $prefix,
        );
    }
}
