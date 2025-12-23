<?php

namespace Tuto\Application\Loaders;

interface LoaderInterface
{
    /**
     * @return void
     */
    public function load(): void;
}