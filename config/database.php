<?php

use Tuto\Container\DependencyInjectionContainer;
use Tuto\Container\Items\DependencyItem;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\Pdo\PdoConnection;

return [
    'database.type' => env('DB_TYPE', 'mysql'),
    'database.host' => env('DB_HOST', 'localhost'),
    'database.port' => env('DB_PORT', 3306),
    'database.username' => env('DB_USERNAME', 'root'),
    'database.password' => env('DB_PASSWORD', 'root'),
    'database.name' => env('DB_NAME', 'project'),

    DependencyItem::factory(
        ConnectionInterface::class,
        static fn (DependencyInjectionContainer $container) => new PdoConnection(
            $container->get('database.type'),
            $container->get('database.host'),
            $container->get('database.port'),
            $container->get('database.name'),
            $container->get('database.username'),
            $container->get('database.password'),
        )
    )
];
