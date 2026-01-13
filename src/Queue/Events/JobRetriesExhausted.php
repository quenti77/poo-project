<?php

namespace Tuto\Queue\Events;

use Tuto\Event\Event;

class JobRetriesExhausted extends Event
{
    /**
     * @param string $jobEntityId
     * @param int $maxAttempts
     */
    public function __construct(
        public readonly string $jobEntityId,
        public readonly int $maxAttempts,
    ) {
    }
}
