<?php

namespace Tuto\TemplateOld;

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

        $debug = (bool) $container->getWithoutError('template.debug', false);

        return new Engine($templatePath, $cachePath, $debug);
    }
}
