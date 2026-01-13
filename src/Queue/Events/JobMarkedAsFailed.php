<?php

namespace Tuto\Queue\Events;

use Throwable;
use Tuto\Event\Event;

class JobMarkedAsFailed extends Event
{
    /**
     * @param string $jobEntityId
     */
    public function __construct(public readonly string $jobEntityId)
    {
    }
}
