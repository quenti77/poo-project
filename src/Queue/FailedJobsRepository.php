<?php

namespace Tuto\Queue;

use DateMalformedStringException;
use JsonException;
use Tuto\Collections\Collection;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\Query\Conditions\ConditionOperator;
use Tuto\Database\Query\QueryMaker;
use Tuto\Queue\Jobs\FailedJobEntity;
use Tuto\Utils\DataTransformer;

class FailedJobsRepository
{
    use DataTransformer;

    /** @var string[] All fields on jobs table */
    private const array FAILED_JOBS_FIELDS = ['id', 'queue', 'payload', 'exception', 'failed_at'];

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    /**
     * @param string $queue
     * @return int
     */
    public function count(string $queue): int
    {
        $query = QueryMaker::select('count(*) as nb')
            ->from('failed_jobs')
            ->where('queue', $queue)
            ->render()
            ->makeRequest($this->connection);

        return $query->fetch()['nb'] ?? 0;
    }

    /**
     * @param string $queue
     * @param string|null $id
     * @param int $limit
     * @return Collection<int, FailedJobEntity>
     * @throws DateMalformedStringException
     * @throws JsonException
     */
    public function getJobs(string $queue, string|null $id, int $limit): Collection
    {
        $query = QueryMaker::select(self::FAILED_JOBS_FIELDS)
            ->from('failed_jobs')
            ->where('queue', $queue)
            ->orderBy('id')
            ->limit($limit);

        if ($id) {
            $query->where('id', $id);
        }
        $request = $query->render()->makeRequest($this->connection);

        $failedJobs = collect();
        while ($data = $request->fetch()) {
            $failedJob = new FailedJobEntity(
                id: $this->transformToUlid($data['id']),
                queue: $data['queue'],
                payload: $this->transformJsonToArray($data['payload']),
                exception: $data['exception'],
                failedAt: $this->transformToDateTime($data['failed_at']),
            );
            $failedJobs->push($failedJob);
        }

        return $failedJobs;
    }

    /**
     * @param Collection<int, FailedJobEntity> $failedJobs
     * @return void
     */
    public function deleteJobs(Collection $failedJobs): void
    {
        $ids = $failedJobs->map(static fn (int $key, FailedJobEntity $failedJob) => $failedJob->getId()->value);

        QueryMaker::delete('failed_jobs')
            ->where('id', 'IN', $ids)
            ->render()
            ->makeRequest($this->connection);
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