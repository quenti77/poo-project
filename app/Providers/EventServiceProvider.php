<?php

namespace App\Providers;

use App\TestSubscriber;
use RuntimeException;
use Tuto\Application\ServiceProvider\ServiceProviderInterface;
use Tuto\Event\Contract\EventDispatcherInterface;
use Tuto\Event\Contract\EventSubscriberInterface;

class EventServiceProvider implements ServiceProviderInterface
{
    /** @var array<string, array<int, string>> $listeners */
    protected array $listeners = [];

    /** @var array<int, string> $subscribers */
    protected array $subscribers = [];

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
        foreach ($this->listeners as $event => $listeners) {
            foreach ($listeners as $listener) {
                $this->dispatcher->listen($event, $listener);
            }
        }

        foreach ($this->subscribers as $subscriberClass) {
            $subscriber = container($subscriberClass);
            if (!($subscriber instanceof EventSubscriberInterface)) {
                throw new RuntimeException(sprintf("'%s' must be an instance of '%s'", $subscriberClass, EventSubscriberInterface::class));
            }
            $subscriber->subscribe($this->dispatcher);
        }
    }
}
