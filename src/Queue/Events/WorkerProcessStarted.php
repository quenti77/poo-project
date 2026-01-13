<?php

namespace Tuto\Queue\Events;

use Tuto\Event\Event;

class WorkerProcessStarted extends Event
{
    /**
     * @param string $queue
     */
    public function __construct(public readonly string $queue)
    {
    }
}
