<?php

namespace Tuto\Queue\Events;

class JobStarted extends JobEvent
{
    /**
     * @param string $jobEntityId
     * @param string $jobClass
     */
    public function __construct(string $jobEntityId, string $jobClass)
    {
        parent::__construct($jobEntityId, $jobClass, JobStatus::PROCESSING);
    }
}
