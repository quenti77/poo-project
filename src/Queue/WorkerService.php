<?php

namespace Tuto\Queue;

use DateMalformedStringException;
use InvalidArgumentException;
use JsonException;
use Random\RandomException;
use RuntimeException;
use Throwable;
use Tuto\Base\ClassNotFoundException;
use Tuto\Error\ErrorFactory;
use Tuto\Queue\Events\JobCrashed;
use Tuto\Queue\Events\JobFailed;
use Tuto\Queue\Events\JobFinished;
use Tuto\Queue\Events\JobMarkedAsFailed;
use Tuto\Queue\Events\JobRetriesExhausted;
use Tuto\Queue\Events\JobRetryScheduled;
use Tuto\Queue\Events\JobStarted;
use Tuto\Queue\Events\WorkerAfterMaxJobsStopped;
use Tuto\Queue\Events\WorkerProcessStarted;
use Tuto\Queue\Jobs\FailedJobEntity;
use Tuto\Queue\Jobs\JobEntity;
use Tuto\Queue\Jobs\JobInterface;
use Tuto\Utils\CurrentTime;

class WorkerService
{
    /**
     * @param JobsRepository $jobsRepository
     * @param FailedJobsRepository $failedJobsRepository
     * @param CurrentTime $currentTime
     */
    public function __construct(
        private readonly JobsRepository $jobsRepository,
        private readonly FailedJobsRepository $failedJobsRepository,
        private readonly CurrentTime $currentTime,
    ) {
    }

    /**
     * @param string $queue
     * @param int $maxJobs
     * @param int $sleep
     * @return void
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws RandomException
     */
    public function work(string $queue = 'default', int $maxJobs = 0, int $sleep = 3): void
    {
        if ($sleep < 1) {
            throw new InvalidArgumentException("Sleep time must be greater or equal than 1, '{$sleep}' given");
        }

        $processed = 0;

        event(new WorkerProcessStarted($queue));
        while (true) {
            $jobEntity = $this->jobsRepository->pop($queue);

            if ($jobEntity === null) {
                sleep($sleep);
                continue;
            }

            $this->processJob($jobEntity);
            $processed += 1;

            if ($maxJobs > 0 && $processed >= $maxJobs) {
                event(new WorkerAfterMaxJobsStopped($queue, $maxJobs));
                break;
            }
        }
    }

    /**
     * @param JobEntity $jobEntity
     * @return void
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws RandomException
     */
    private function processJob(JobEntity $jobEntity): void
    {
        try {
            $job = $this->getJob($jobEntity);
            event(new JobStarted($jobEntity->getId(), $jobEntity->getJobClass()));

            $job->handle();

            $this->jobsRepository->delete($jobEntity);
            event(new JobFinished($jobEntity->getId(), $jobEntity->getJobClass()));
        } catch (Throwable|ClassNotFoundException $exception) {
            event(new JobFailed($jobEntity->getId(), $jobEntity->getJobClass(), $exception));
            $this->handleFailedJob($jobEntity, $exception);
        }
    }

    /**
     * @param JobEntity $jobEntity
     * @param Throwable $exception
     * @return void
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws RandomException
     */
    private function handleFailedJob(JobEntity $jobEntity, Throwable $exception): void
    {
        if ($exception instanceof ClassNotFoundException) {
            $this->markJobAsFailed($jobEntity, null, $exception);
            return;
        }

        $job = $this->getJob($jobEntity);
        $jobEntity->fail($this->currentTime->now());

        if ($jobEntity->canRetry($job->getMaxAttempts())) {
            $retryAt = $this->currentTime->now()->modify('+1 minute');
            $this->jobsRepository->release($jobEntity, $retryAt);
            event(new JobRetryScheduled($jobEntity->getId(), $retryAt));

            $log = "Releasing job #{$jobEntity->getId()} (attempts {$jobEntity->getAttempts()} / {$job->getMaxAttempts()})";
            logger()->warning($log, [
                'job_id' => $jobEntity->getId(),
                'job_class' => $jobEntity->getJobClass(),
                'attempts' => $jobEntity->getAttempts(),
                'max_attempts' => $job->getMaxAttempts(),
                'retry_at' => $retryAt->format('Y-m-d H:i:s'),
            ]);
            return;
        }

        $this->markJobAsFailed($jobEntity, $job, $exception);
    }

    /**
     * @param JobEntity $jobEntity
     * @param JobInterface|null $job
     * @param Throwable $exception
     * @return void
     * @throws JsonException
     * @throws RandomException
     */
    private function markJobAsFailed(JobEntity $jobEntity, JobInterface|null $job, Throwable $exception): void
    {
        $log = $job === null
            ? "Jobs #{$jobEntity->getId()} failed because '{$exception->getMessage()}'"
            : "Jobs #{$jobEntity->getId()} failed after retries {$job->getMaxAttempts()} attempts";

        $event = $job === null
            ? new JobCrashed($jobEntity->getId(), $exception)
            : new JobRetriesExhausted($jobEntity->getId(), $job->getMaxAttempts());
        event($event);

        $context = [
            'job_id' => $jobEntity->getId(),
            'job_class' => $jobEntity->getJobClass(),
            'attempts' => $jobEntity->getAttempts(),
        ];
        if ($job) {
            $context['max_attempts'] = $job->getMaxAttempts();
        }
        logger()->error($log, $context);

        $errorDetails = ErrorFactory::fromThrowable($exception);
        $failedJob = FailedJobEntity::fromJobError($jobEntity, $errorDetails, $this->currentTime->now());
        $this->failedJobsRepository->store($failedJob);

        $this->jobsRepository->delete($jobEntity);
        event(new JobMarkedAsFailed($jobEntity->getId()));
    }

    /**
     * @param JobEntity $jobEntity
     * @return JobInterface
     */
    private function getJob(JobEntity $jobEntity): JobInterface
    {
        $job = unserialize($jobEntity->getPayload()['data'], ['allowed_classes' => true]);
        if (!($job instanceof JobInterface)) {
            throw new RuntimeException("Invalid jobs in payload for jobs '{$jobEntity->getId()}'");
        }

        return $job;
    }
}
