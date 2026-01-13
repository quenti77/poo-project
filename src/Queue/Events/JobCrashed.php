<?php

namespace Tuto\Queue\Events;

use Throwable;
use Tuto\Event\Event;

class JobCrashed extends Event
{
    /**
     * @param string $jobEntityId
     * @param Throwable $exception
     */
    public function __construct(
        public readonly string $jobEntityId,
        public readonly Throwable $exception,
    ) {
    }
}
