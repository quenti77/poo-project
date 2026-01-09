<?php

namespace Tuto\Event\Contract;

interface ShouldQueueInterface
{
    /**
     * @return string
     */
    public function getQueue(): string;

    /**
     * @return int
     */
    public function getMaxAttempts(): int;
}
