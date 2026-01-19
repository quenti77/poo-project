<?php

namespace Tuto\Application\Loaders;

use Tuto\Application\ServiceProvider\ServiceProviderInterface;

class ServiceProviderLoader implements LoaderInterface
{
    /**
     * @return void
     */
    public function load(): void
    {
        /** @see .docs/informations.md (## ServiceProvider) */
        $serviceProviders = container()->getWithoutError('service.provider', []);
        foreach ($serviceProviders as $serviceProviderClass) {
            $serviceProvider = container($serviceProviderClass);
            if (!($serviceProvider instanceof ServiceProviderInterface)) {
                continue;
            }
            $serviceProvider->register();
        }
    }
}
