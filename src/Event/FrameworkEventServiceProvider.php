<?php

namespace Tuto\Event;

use Tuto\Application\ServiceProvider\EventServiceProviderTrait;
use Tuto\Application\ServiceProvider\ServiceProviderInterface;
use Tuto\Console\Listeners\ConsoleMigrationSubscriber;
use Tuto\Event\Contract\EventSubscriberInterface;

class FrameworkEventServiceProvider implements ServiceProviderInterface
{
    use EventServiceProviderTrait;

    /**
     * @return array<string, array<int, string>>
     */
    public function getListeners(): array
    {
        return [];
    }

    /**
     * @return array<int, class-string<EventSubscriberInterface>>
     */
    public function getSubscribers(): array
    {
        return [
            ConsoleMigrationSubscriber::class,
        ];
    }
}
