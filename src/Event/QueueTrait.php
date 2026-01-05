<?php

namespace Tuto\Event;

trait QueueTrait
{
    /**
     * @return string
     */
    public function getQueue(): string
    {
        return 'default';
    }

    /**
     * @return int
     */
    public function getMaxAttempts(): int
    {
        return 3;
    }
}