<?php

use App\Providers\EventServiceProvider;
use App\Providers\TemplateServiceProvider;
use Tuto\Event\FrameworkEventServiceProvider;

return [
    'service.provider' => [
        FrameworkEventServiceProvider::class,
        EventServiceProvider::class,
        TemplateServiceProvider::class,
    ],
];
