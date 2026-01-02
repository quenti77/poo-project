<?php

namespace Tuto\Queue;

class QueueStats
{
    /**
     * @param string $queue
     * @param int $total
     * @param int $pending
     * @param int $reserved
     * @param int $delayed
     * @param int $failed
     */
    public function __construct(
        public readonly string $queue,
        public readonly int $total = 0,
        public readonly int $pending = 0,
        public readonly int $reserved = 0,
        public readonly int $delayed = 0,
        private int $failed = 0,
    ) {
    }

    /**
     * @return int
     */
    public function getFailed(): int
    {
        return $this->failed;
    }

    /**
     * @param int $failed
     * @return void
     */
    public function setFailed(int $failed): void
    {
        $this->failed = $failed;
    }
}