<?php

namespace Tuto\Application\ServiceProvider;

use RuntimeException;
use Tuto\Event\Contract\EventDispatcherInterface;
use Tuto\Event\Contract\EventSubscriberInterface;

trait EventServiceProviderTrait
{
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
        return [];
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     */
    public function __construct(private readonly EventDispatcherInterface $dispatcher)
    {
    }

    /**
     * @return void
     */
    public function register(): void
    {
        foreach ($this->getListeners() as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->dispatcher->listen($event, $listener);
            }
        }

        foreach ($this->getSubscribers() as $subscriberClass) {
            $subscriber = container($subscriberClass);
            if (!($subscriber instanceof EventSubscriberInterface)) {
                throw new RuntimeException(sprintf("'%s' must be an instance of '%s'", $subscriberClass, EventSubscriberInterface::class));
            }
            $subscriber->subscribe($this->dispatcher);
        }
    }
}
