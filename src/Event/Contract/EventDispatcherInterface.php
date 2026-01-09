<?php

namespace Tuto\Event\Contract;

use Tuto\Collections\Collection;

interface EventDispatcherInterface
{
    /**
     * @param string $eventName
     * @param callable|string $listener
     * @param int $priority
     * @return void
     */
    public function listen(string $eventName, callable|string $listener, int $priority = 0): void;

    /**
     * @param EventSubscriberInterface $subscriber
     * @return void
     */
    public function subscribe(EventSubscriberInterface $subscriber): void;

    /**
     * @param EventInterface $event
     * @return EventInterface
     */
    public function dispatch(EventInterface $event): EventInterface;

    /**
     * @param string $eventName
     * @return bool
     */
    public function hasListeners(string $eventName): bool;

    /**
     * @param string $eventName
     * @return Collection<int, callable|string>
     */
    public function getListeners(string $eventName): Collection;
}
