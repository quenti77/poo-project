<?php

use Tuto\Container\DependencyInjectionContainer;
use Tuto\Container\Items\DependencyItem;
use Tuto\Translate\Translator;
use Tuto\Translate\TranslatorFactory;

return [
    'locale.path' => env('LOCAL_FOLDER', 'storage/lang'),
    'locale.enabled' => ['en-US', 'fr-FR'],
    'locale.fallback' => env('LOCAL_FALLBACK', 'en-US'),
    'locale.cookie_name' => env('LOCAL_COOKIE_NAME', 'locale'),

    DependencyItem::single(
        Translator::class,
        static fn (DependencyInjectionContainer $container) => TranslatorFactory::make(
            $container->get('locale.path'),
            $container->get('locale.enabled'),
            $container->get('locale.fallback'),
        ),
    ),
];
