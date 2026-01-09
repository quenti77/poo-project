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
        foreach ($this->loaders() as $loader) {
            $loader->load();
        }
        $this->run();
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
