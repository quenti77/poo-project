<?php

namespace Tuto\Queue\Events;

use DateTimeImmutable;
use Tuto\Event\Event;

class JobRetryScheduled extends Event
{
    /**
     * @param string $jobEntityId
     * @param DateTimeImmutable $retryAt
     */
    public function __construct(
        public readonly string $jobEntityId,
        public readonly DateTimeImmutable $retryAt,
    ) {
    }
}
