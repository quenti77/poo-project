<?php

namespace Tuto\Queue\Jobs;

use DateTimeImmutable;
use JsonException;
use Random\RandomException;
use Tuto\Utils\Ulid;

class JobEntity
{
    use JobEntityTrait;

    /**
     * @param Ulid $id
     * @param string $queue
     * @param array $payload
     * @param int $attempts
     * @param DateTimeImmutable|null $reservedAt
     * @param DateTimeImmutable $availableAt
     * @param DateTimeImmutable $createdAt
     * @param DateTimeImmutable $updatedAt
     */
    public function __construct(
        Ulid $id,
        string $queue,
        array $payload,
        private int $attempts,
        private DateTimeImmutable|null $reservedAt,
        private readonly DateTimeImmutable $availableAt,
        private readonly DateTimeImmutable $createdAt,
        private DateTimeImmutable $updatedAt,
    ) {
        $this->id = $id;
        $this->queue = $queue;
        $this->payload = $payload;
    }

    /**
     * @param JobInterface $job
     * @param string $queue
     * @param DateTimeImmutable $currentAt
     * @return self
     * @throws JsonException
     * @throws RandomException
     * @throws \DateMalformedStringException
     */
    public static function fromJob(JobInterface $job, string $queue, DateTimeImmutable $currentAt): self
    {
        $payload = [
            'class' => $job::class,
            'data' => serialize($job),
        ];

        return new self(
            id: Ulid::next(),
            queue: $queue,
            payload: $payload,
            attempts: 0,
            reservedAt: null,
            availableAt: $currentAt->modify("+{$job->getDelay()} second"),
            createdAt: $currentAt,
            updatedAt: $currentAt,
        );
    }

    /**
     * @return int
     */
    public function getAttempts(): int
    {
        return $this->attempts;
    }

    /**
     * @param int $maxAttempts
     * @return bool
     */
    public function canRetry(int $maxAttempts): bool
    {
        return $this->attempts < $maxAttempts;
    }

    /**
     * @param DateTimeImmutable $currentAt
     * @return void
     */
    public function fail(DateTimeImmutable $currentAt): void
    {
        $this->attempts += 1;
        $this->update($currentAt);
    }

    /**
     * @return DateTimeImmutable|null
     */
    public function getReservedAt(): ?DateTimeImmutable
    {
        return $this->reservedAt;
    }

    /**
     * @return bool
     */
    public function isReserved(): bool
    {
        return $this->reservedAt !== null;
    }

    /**
     * @param DateTimeImmutable $reservedAt
     * @param DateTimeImmutable $currentAt
     * @return void
     */
    public function reserve(DateTimeImmutable $reservedAt, DateTimeImmutable $currentAt): void
    {
        $this->reservedAt = $reservedAt;
        $this->update($currentAt);
    }

    /**
     * @return DateTimeImmutable
     */
    public function getAvailableAt(): DateTimeImmutable
    {
        return $this->availableAt;
    }

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
     * @param DateTimeImmutable $currentAt
     * @return void
     */
    private function update(DateTimeImmutable $currentAt): void
    {
        $this->updatedAt = $currentAt;
    }
}