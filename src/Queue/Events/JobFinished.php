<?php

namespace Tuto\Queue\Events;

class JobFinished extends JobEvent
{
    /**
     * @param string $jobEntityId
     * @param string $jobClass
     */
    public function __construct(string $jobEntityId, string $jobClass)
    {
        parent::__construct($jobEntityId, $jobClass, JobStatus::FINISHED);
    }
}
