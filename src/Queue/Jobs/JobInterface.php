<?php

namespace Tuto\Queue\Jobs;

interface JobInterface
{
    /**
     * @return int
     */
    public function getMaxAttempts(): int;

    /**
     * @return int
     */
    public function getDelay(): int;

    /**
     * @return void
     */
    public function handle(): void;
}
