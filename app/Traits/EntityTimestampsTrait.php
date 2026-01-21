<?php

namespace App\Traits;

use DateTimeImmutable;
use InvalidArgumentException;

trait EntityTimestampsTrait
{
    /** @var DateTimeImmutable $createdAt */
    private readonly DateTimeImmutable $createdAt;

    /** @var DateTimeImmutable $updatedAt */
    private DateTimeImmutable $updatedAt;

    /**
     * @return DateTimeImmutable
     */
    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @param DateTimeImmutable $current
     * @return void
     */
    public function update(DateTimeImmutable $current): void
    {
        if ($this->createdAt > $current) {
            throw new InvalidArgumentException("new DateTime must be greater than or equals to createdAt");
        }
        $this->updatedAt = $current;
    }
}