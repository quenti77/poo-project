<?php

use App\Providers\EventServiceProvider;
use Tuto\Event\FrameworkEventServiceProvider;

return [
    'service.provider' => [
        FrameworkEventServiceProvider::class,
        EventServiceProvider::class,
    ],
];
