<?php

namespace Tuto\Queue\Jobs;

abstract class AbstractJob implements JobInterface
{
    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return 3;
    }

    /**
     * @return int
     */
    public function getDelay(): int
    {
        return 0;
    }

    /**
     * @return void
     */
    abstract public function handle(): void;
}
