<?php

namespace Tuto\Queue\Jobs;

use RuntimeException;
use Tuto\Utils\Ulid;

trait JobEntityTrait
{
    /** @var Ulid $id */
    private readonly Ulid $id;

    /** @var string $queue */
    private readonly string $queue;

    /** @var array $payload */
    private readonly array $payload;

    /**
     * @return Ulid
     */
    public function getId(): Ulid
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getQueue(): string
    {
        return $this->queue;
    }

    /**
     * @return array
     */
    public function getPayload(): array
    {
        return $this->payload;
    }

    /**
     * @return string
     */
    public function getJobClass(): string
    {
        return $this->payload['class'] ?? throw new RuntimeException('Missing class offset in job payload');
    }
}