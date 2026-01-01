<?php

namespace Tuto\Queue;

use Tuto\Database\ConnectionInterface;
use Tuto\Database\Query\QueryMaker;
use Tuto\Queue\Jobs\FailedJobEntity;

class FailedJobsRepository
{
    /** @var string[] All fields on jobs table */
    private const array FAILED_JOBS_FIELDS = ['id', 'queue', 'payload', 'exception', 'failed_at'];

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    /**
     * @param FailedJobEntity $failedJobEntity
     * @return void
     */
    public function store(FailedJobEntity $failedJobEntity): void
    {
        QueryMaker::insert('failed_jobs')
            ->values([
                'id' => $failedJobEntity->getId(),
                'queue' => $failedJobEntity->getQueue(),
                'payload' => $failedJobEntity->getPayload(),
                'exception' => $failedJobEntity->getException(),
                'failed_at' => $failedJobEntity->getFailedAt(),
            ])
            ->render()
            ->makeRequest($this->connection);
    }
}