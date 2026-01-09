<?php

namespace Tuto\Queue;

use DateMalformedStringException;
use InvalidArgumentException;
use JsonException;
use Random\RandomException;
use RuntimeException;
use Throwable;
use Tuto\Base\ClassNotFoundException;
use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\Output;
use Tuto\Error\ErrorFactory;
use Tuto\Queue\Jobs\FailedJobEntity;
use Tuto\Queue\Jobs\JobEntity;
use Tuto\Queue\Jobs\JobInterface;
use Tuto\Utils\CurrentTime;

class WorkerService
{
    private Output|null $output = null;

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
     * @param Output $output
     * @return void
     */
    public function withOutput(Output $output): void
    {
        $this->output = $output;
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

        $this->output?->success("Worker started on queue: '{$queue}'");
        $this->output?->writeln();

        while (true) {
            $jobEntity = $this->jobsRepository->pop($queue);

            if ($jobEntity === null) {
                sleep($sleep);
                continue;
            }

            $this->processJob($jobEntity);
            $processed += 1;

            if ($maxJobs > 0 && $processed >= $maxJobs) {
                $this->output?->info("Max jobs reached. Stopping worker...");
                $this->output?->writeln();
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
        $outputInfo = "Jobs #{$jobEntity->getId()} '{$jobEntity->getJobClass()}'";

        try {
            $job = $this->getJob($jobEntity);

            $this->output?->write($outputInfo . ' ');
            $this->output?->badge("doing", Ansi::FG_YELLOW);

            $job->handle();

            $this->jobsRepository->delete($jobEntity);
            $this->output?->write($outputInfo . ' ');
            $this->output?->badge("done", Ansi::FG_GREEN);
        } catch (Throwable|ClassNotFoundException $exception) {
            $this->output?->write($outputInfo . ' ');
            $this->output?->badge("error", Ansi::FG_RED);

            $this->handleFailedJob($jobEntity, $exception);
        }

        $this->output?->writeln();
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
            $log = "Releasing job #{$jobEntity->getId()} (attempts {$jobEntity->getAttempts()} / {$job->getMaxAttempts()})";
            $this->jobsRepository->release($jobEntity, $this->currentTime->now()->modify('+1 minute'));
            $this->output?->warning($log . ' ');
            $this->output?->writeln();

            logger()->warning($log, [
                'job_id' => $jobEntity->getId(),
                'job_class' => $jobEntity->getJobClass(),
                'attempts' => $jobEntity->getAttempts(),
                'max_attempts' => $job->getMaxAttempts(),
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

        $this->output?->error($log);
        $this->output?->writeln();

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
        $this->output?->warning("Jobs moved to failed jobs");
        $this->output?->writeln();
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
