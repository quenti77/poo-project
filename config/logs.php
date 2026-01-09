<?php

use Tuto\Container\DependencyInjectionContainer;
use Tuto\Container\Items\DependencyItem;
use Tuto\Logger\LoggerFactory;
use Tuto\Logger\LoggerInterface;

return [
    // Driver : 'daily' or 'syslog'
    'logger.driver' => env('LOG_DRIVER', 'daily'),

    // Use for daily
    'logger.path' => env('LOG_PATH', 'storage/logs'),

    // Identifier
    'logger.identifier' => env('LOG_IDENTIFIER', 'app'),

    // Log level needed
    'logger.min_level' => env('LOG_LEVEL', 'debug'),

    DependencyItem::single(
        LoggerInterface::class,
        LoggerFactory::make(...),
    ),
];
