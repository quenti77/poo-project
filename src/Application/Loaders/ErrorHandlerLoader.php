<?php

namespace Tuto\Application\Loaders;

use Tuto\Error\ErrorHandler;

class ErrorHandlerLoader implements LoaderInterface
{
    /**
     * @return void
     */
    public function load(): void
    {
        ErrorHandler::register();
    }
}