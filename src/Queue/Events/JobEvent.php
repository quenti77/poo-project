<?php

namespace Tuto\Queue\Events;

use Tuto\Event\Event;

class JobEvent extends Event
{
    /**
     * @param string $jobEntityId
     * @param string $jobClass
     * @param JobStatus $status
     */
    public function __construct(
        public readonly string $jobEntityId,
        public readonly string $jobClass,
        public readonly JobStatus $status,
    ) {
    }
}
