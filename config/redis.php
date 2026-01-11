<?php

use Tuto\Container\DependencyInjectionContainer;
use Tuto\Container\Items\DependencyItem;
use Tuto\Database\Redis\RedisConnection;

return [
    'redis.host' => env('REDIS_HOST', '127.0.0.1'),
    'redis.port' => (int) env('REDIS_PORT', 6379),
    'redis.password' => env('REDIS_PASSWORD', null),
    'redis.database' => (int) env('REDIS_DATABASE', 0),
    'redis.timeout' => (int) env('REDIS_TIMEOUT', 2),

    DependencyItem::single(
        RedisConnection::class,
        static fn (DependencyInjectionContainer $container) => new RedisConnection(
            $container->get('redis.host'),
            $container->get('redis.port'),
            $container->get('redis.password'),
            $container->get('redis.database'),
            $container->get('redis.timeout'),
        ),
    )
];
