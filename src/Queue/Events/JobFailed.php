<?php

namespace Tuto\Queue\Events;

use Throwable;

class JobFailed extends JobEvent
{
    /**
     * @param string $jobEntityId
     * @param string $jobClass
     * @param Throwable $exception
     */
    public function __construct(string $jobEntityId, string $jobClass, public readonly Throwable $exception)
    {
        parent::__construct($jobEntityId, $jobClass, JobStatus::ERROR);
    }
}
