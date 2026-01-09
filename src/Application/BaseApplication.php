<?php

namespace Tuto\Application;

use Tuto\Application\Loaders\LoaderInterface;
use Tuto\Collections\Collection;

abstract class BaseApplication
{
    public function __construct()
    {
    }

    public function boot(): void
    {
        ob_start();

        foreach ($this->loaders() as $loader) {
            $loader->load();
        }
        $this->run();

        echo ob_get_clean();
    }

    /**
     * @return Collection<int, LoaderInterface>
     */
    abstract protected function loaders(): Collection;

    /**
     * @return void
     */
    abstract protected function run(): void;
}
