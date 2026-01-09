<?php

namespace Tuto\Application\Loaders;

use Tuto\Console\Components\Output;
use Tuto\Error\ErrorCliHandler;
use Tuto\Error\ErrorHandler;

class ErrorHandlerLoader implements LoaderInterface
{
    /**
     * @param Output|null $output
     */
    public function __construct(private readonly Output|null $output = null)
    {
    }

    /**
     * @return void
     */
    public function load(): void
    {
        if ($this->output) {
            ErrorCliHandler::register();
            ErrorCliHandler::withOutput($this->output);
        } else {
            ErrorHandler::register();
        }
    }
}
