<?php

namespace Tuto\Queue\Jobs;

use DateTimeImmutable;
use JsonException;
use Random\RandomException;
use Tuto\Error\ErrorDetails;
use Tuto\Utils\ValueObject\Ulid;

class FailedJobEntity
{
    use JobEntityTrait;

    /**
     * @param Ulid $id
     * @param string $queue
     * @param array $payload
     * @param string $exception
     * @param DateTimeImmutable $failedAt
     */
    public function __construct(
        Ulid $id,
        string $queue,
        array $payload,
        private readonly string $exception,
        private readonly DateTimeImmutable $failedAt,
    ) {
        $this->id = $id;
        $this->queue = $queue;
        $this->payload = $payload;
    }

    /**
     * @param JobEntity $jobEntity
     * @param ErrorDetails $errorDetails
     * @param DateTimeImmutable $failedAt
     * @return self
     * @throws JsonException
     * @throws RandomException
     */
    public static function fromJobError(
        JobEntity $jobEntity,
        ErrorDetails $errorDetails,
        DateTimeImmutable $failedAt,
    ): self {
        return new self(
            id: Ulid::next(),
            queue: $jobEntity->getQueue(),
            payload: $jobEntity->getPayload(),
            exception: json_encode($errorDetails, JSON_THROW_ON_ERROR),
            failedAt: $failedAt,
        );
    }

    /**
     * @return string
     */
    public function getException(): string
    {
        return $this->exception;
    }

    /**
     * @return DateTimeImmutable
     */
    public function getFailedAt(): DateTimeImmutable
    {
        return $this->failedAt;
    }
}
