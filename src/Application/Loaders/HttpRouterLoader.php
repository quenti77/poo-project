<?php

namespace Tuto\Application\Loaders;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use SplFileInfo;
use Tuto\Middleware\Global\LocaleMiddleware;
use Tuto\Middleware\Global\MaintenanceModeMiddleware;

class HttpRouterLoader implements LoaderInterface
{
    /**
     * @return void
     * @throws ReflectionException
     */
    public function load(): void
    {
        $this->addGlobalMiddlewares();

        // Find all files in config folder
        $rdi = new RecursiveDirectoryIterator(container('path.router'), FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            // Load routes if file is a PHP file
            if ($file->getExtension() === 'php') {
                require $file->getRealPath();
            }
        }
    }

    /**
     * @return void
     */
    private function addGlobalMiddlewares(): void
    {
        // Order is very important
        router()->middlewares([
            LocaleMiddleware::class,
            MaintenanceModeMiddleware::class,
        ]);
    }
}
