<?php

use Tuto\Container\Items\DependencyItem;
use Tuto\TemplateOld\Engine;
use Tuto\TemplateOld\TemplateFactory;

return [
    'template.path' => env('TEMPLATE_PATH', 'views'),
    'template.cache_path' => env('TEMPLATE_CACHE_PATH', 'storage/framework/views'),
    'template.debug' => env('TEMPLATE_DEBUG', env('APP_DEBUG', false)),

    DependencyItem::single(
        Engine::class,
        TemplateFactory::make(...),
    ),
];
