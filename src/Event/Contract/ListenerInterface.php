<?php

namespace Tuto\Event\Contract;

interface ListenerInterface
{
    /**
     * @param EventInterface $event
     * @return void
     */
    public function handle(EventInterface $event): void;
}