<?php

namespace Tuto\Template;

use Tuto\Container\DependencyInjectionContainer;
use Tuto\Utils\File;

class TemplateFactory
{
    public static function make(DependencyInjectionContainer $container): Engine
    {
        $templatePath = $container->getWithoutError('template.path', 'views');
        $templatePath = File::absolute($templatePath);

        $cachePath = $container->getWithoutError('template.cache_path', 'storage/framework/views');
        $cachePath = File::absolute($cachePath);

        $debug = (bool)$container->getWithoutError('template.debug', false);

        $engine = new Engine($templatePath, $cachePath, $debug);
        return self::addFilters($engine);
    }

    private static function addFilters(Engine $engine): Engine
    {
        $engine->addFilter('trans', static fn(string $k, int|float $l = 1, array $c = []) => trans($k, $l, $c));

        $engine->addFunction('url', static fn(string $name, array $params = []) => router()->generate($name, $params, true));
        $engine->addFunction('path', static fn(string $name, array $params = []) => router()->generate($name, $params, false));

        $engine->addFunction('can', static fn(int $minLevel) => request()->session->get('user')?->getGroup()->getLevel()->canAccess($minLevel));
        $engine->addFunction('adminLevel', static fn () => 30);

        return $engine;
    }
}
