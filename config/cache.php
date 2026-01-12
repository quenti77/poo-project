<?php

use Tuto\Cache\CacheFactory;
use Tuto\Cache\CacheInterface;
use Tuto\Container\Items\DependencyItem;

return [
    'cache.driver' => env('CACHE_DRIVER', 'file'),
    'cache.prefix' => env('CACHE_PREFIX', 'poo_cache'),

    'cache.file.path' => env('CACHE_FILE_PATH', 'storage/framework/cache'),

    DependencyItem::single(
        CacheInterface::class,
        CacheFactory::make(...),
    ),
];
