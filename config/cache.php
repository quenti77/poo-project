<?php

use Tuto\Cache\CacheFactory;
use Tuto\Cache\CacheInterface;
use Tuto\Container\DependencyInjectionContainer;
use Tuto\Container\Items\DependencyItem;

return [
    'cache.driver' => env('CACHE_DRIVER', 'file'),
    'cache.prefix' => env('CACHE_PREFIX', 'poo_cache'),

    'cache.file.path' => env('CACHE_FILE_PATH', 'storage/framework/cache'),

    'cache.redis.host' => env('REDIS_HOST', '127.0.0.1'),
    'cache.redis.port' => env('REDIS_PORT', 6379),
    'cache.redis.password' => env('REDIS_PASSWORD'),
    'cache.redis.database' => env('CACHE_REDIS_DATABASE', 0),
    'cache.redis.timeout' => env('CACHE_REDIS_TIMEOUT', 2),

    DependencyItem::single(
        CacheInterface::class,
        static fn (DependencyInjectionContainer $container) => CacheFactory::make($container),
    ),
];
