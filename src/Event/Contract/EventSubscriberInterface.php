<?php

namespace Tuto\Event\Contract;

interface EventSubscriberInterface
{
    /**
     * @param EventDispatcherInterface $dispatcher
     * @return void
     */
    public function subscribe(EventDispatcherInterface $dispatcher): void;
}