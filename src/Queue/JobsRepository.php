<?php

namespace Tuto\Queue;

use DateMalformedStringException;
use DateTimeImmutable;
use JsonException;
use Random\RandomException;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\Query\QueryMaker;
use Tuto\Queue\Jobs\JobEntity;
use Tuto\Queue\Jobs\JobInterface;
use Tuto\Utils\CurrentTime;
use Tuto\Utils\DataTransformer;
use Tuto\Utils\Ulid;

class JobsRepository
{
    use DataTransformer;

    /** @var string[] All fields on jobs table */
    private const array JOBS_FIELDS = [
        'id', 'queue', 'payload', 'attempts', 'reserved_at', 'available_at', 'created_at', 'updated_at',
    ];

    /**
     * @param ConnectionInterface $connection
     * @param CurrentTime $currentTime
     */
    public function __construct(
        private readonly ConnectionInterface $connection,
        private readonly CurrentTime $currentTime,
    ) {
    }

    /**
     * @param JobInterface $job
     * @param string $queue
     * @return JobEntity
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws RandomException
     */
    public function push(JobInterface $job, string $queue): JobEntity
    {
        $jobEntity = JobEntity::fromJob($job, $queue, $this->currentTime->now());

        QueryMaker::insert('jobs')
            ->values([
                'id' => $jobEntity->getId(),
                'queue' => $jobEntity->getQueue(),
                'payload' => $jobEntity->getPayload(),
                'attempts' => $jobEntity->getAttempts(),
                'reserved_at' => $jobEntity->getReservedAt(),
                'available_at' => $jobEntity->getAvailableAt(),
                'created_at' => $jobEntity->getCreatedAt(),
                'updated_at' => $jobEntity->getUpdatedAt(),
            ])
            ->render()
            ->makeRequest($this->connection);

        return $jobEntity;
    }

    /**
     * @param string $queue
     * @return JobEntity|null
     * @throws JsonException
     * @throws DateMalformedStringException
     */
    public function pop(string $queue): JobEntity|null
    {
        $currentAt = $this->currentTime->now();

        $query = QueryMaker::select(self::JOBS_FIELDS)
            ->from('jobs')
            ->where('queue', $queue)
            ->where('reserved_at', null)
            ->where('available_at', '<=', $currentAt)
            ->orderBy('id')
            ->limit(1);

        $result = $query->render()->makeRequest($this->connection)->fetch();
        if ($result === false) {
            return null;
        }

        return $this->lockJob(new Ulid($result['id']), $currentAt);
    }

    public function release(JobEntity $jobEntity, DateTimeImmutable $availableAt): void
    {
        QueryMaker::update('jobs')
            ->values([
                'reserved_at' => null,
                'available_at' => $availableAt,
                'attempts' => $jobEntity->getAttempts(),
                'updated_at' => $jobEntity->getUpdatedAt(),
            ])
            ->where('id', $jobEntity->getId())
            ->render()
            ->makeRequest($this->connection);
    }

    /**
     * @param JobEntity $jobEntity
     * @return void
     */
    public function delete(JobEntity $jobEntity): void
    {
        QueryMaker::delete('jobs')
            ->where('id', $jobEntity->getId())
            ->render()
            ->makeRequest($this->connection);
    }

    /**
     * @param Ulid $jobId
     * @param DateTimeImmutable $currentAt
     * @return JobEntity|null
     * @throws JsonException
     * @throws DateMalformedStringException
     */
    private function lockJob(Ulid $jobId, DateTimeImmutable $currentAt): JobEntity|null
    {
        QueryMaker::update('jobs')
            ->values(['reserved_at' => $currentAt])
            ->where('id', (string) $jobId)
            ->where('reserved_at', null)
            ->render()
            ->makeRequest($this->connection);

        $jobData = QueryMaker::select(self::JOBS_FIELDS)
            ->from('jobs')
            ->where('id', (string) $jobId)
            ->where('reserved_at', $currentAt)
            ->render()
            ->makeRequest($this->connection)
            ->fetch();

        return $jobData === false ? null : $this->transformToEntity($jobData);
    }

    /**
     * @param array $jobData
     * @return JobEntity
     * @throws JsonException
     * @throws DateMalformedStringException
     */
    private function transformToEntity(array $jobData): JobEntity
    {
        return new JobEntity(
            id: $this->transformToUlid($jobData['id']),
            queue: $jobData['queue'],
            payload: $this->transformJsonToArray($jobData['payload']),
            attempts: (int) $jobData['attempts'],
            reservedAt: $this->transformToDateTimeOrNull($jobData['reserved_at']),
            availableAt: $this->transformToDateTime($jobData['available_at']),
            createdAt: $this->transformToDateTime($jobData['created_at']),
            updatedAt: $this->transformToDateTime($jobData['updated_at']),
        );
    }
}