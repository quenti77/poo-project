<?php

namespace Tuto\Application\ServiceProvider;

interface ServiceProviderInterface
{
    /**
     * @return void
     */
    public function register(): void;
}
