<?php

use Tuto\Container\Items\DependencyItem;
use Tuto\Event\Contract\EventDispatcherInterface;
use Tuto\Event\EventDispatcher;
use Tuto\Http\Requests\Request;

return [
    'app.name' => env('APP_NAME', 'tuto-poo'),
    'app.env' => env('APP_ENV', 'local'),
    'app.debug' => (bool) env('APP_DEBUG', true),
    'app.url' => env('APP_URL', 'http://localhost'),

    'path.router' => ROOT . '/routes',
    'path.database' => ROOT . '/database',

    DependencyItem::single(Request::class, static fn () => request()),

    // All interfaces implementation
    DependencyItem::concrete(EventDispatcherInterface::class, EventDispatcher::class),
];
