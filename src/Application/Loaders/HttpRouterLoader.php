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

        $rdi = new RecursiveDirectoryIterator(container('path.router'), FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
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
        router()->middlewares([
            LocaleMiddleware::class,
            MaintenanceModeMiddleware::class,
        ]);
    }
}