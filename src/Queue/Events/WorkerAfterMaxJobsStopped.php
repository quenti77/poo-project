<?php

namespace Tuto\Queue\Events;

use Tuto\Event\Event;

class WorkerAfterMaxJobsStopped extends Event
{
    /**
     * @param string $queue
     * @param int $maxJobs
     */
    public function __construct(
        public readonly string $queue,
        public readonly int $maxJobs,
    ) {
    }
}
