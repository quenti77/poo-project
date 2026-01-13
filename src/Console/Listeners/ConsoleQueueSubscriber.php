<?php

namespace Tuto\Console\Listeners;

use DateMalformedStringException;
use JsonException;
use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\Output;
use Tuto\Event\Contract\EventDispatcherInterface;
use Tuto\Event\Contract\EventSubscriberInterface;
use Tuto\Queue\Events\JobCrashed;
use Tuto\Queue\Events\JobFailed;
use Tuto\Queue\Events\JobFinished;
use Tuto\Queue\Events\JobMarkedAsFailed;
use Tuto\Queue\Events\JobRetriesExhausted;
use Tuto\Queue\Events\JobRetryScheduled;
use Tuto\Queue\Events\JobStarted;
use Tuto\Queue\Events\WorkerAfterMaxJobsStopped;
use Tuto\Queue\Events\WorkerProcessStarted;
use Tuto\Queue\Jobs\JobInterface;
use Tuto\Queue\JobsRepository;
use Tuto\Utils\ValueObject\Ulid;

class ConsoleQueueSubscriber implements EventSubscriberInterface
{
    /**
     * @param Output $output
     */
    public function __construct(
        private readonly Output $output,
        private readonly JobsRepository $jobsRepository,
    ) {
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @return void
     */
    public function subscribe(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->listen(WorkerProcessStarted::class, $this->onWorkerStart(...));
        $dispatcher->listen(WorkerAfterMaxJobsStopped::class, $this->onWorkerStopAfterMaxJobProcessed(...));
        $dispatcher->listen(JobStarted::class, $this->onJobStart(...));
        $dispatcher->listen(JobFinished::class, $this->onJobFinish(...));
        $dispatcher->listen(JobFailed::class, $this->onJobFail(...));
        $dispatcher->listen(JobRetryScheduled::class, $this->onJobScheduleToRetry(...));
        $dispatcher->listen(JobCrashed::class, $this->onJobCrash(...));
        $dispatcher->listen(JobRetriesExhausted::class, $this->onJobMaxRetryExhaust(...));
        $dispatcher->listen(JobMarkedAsFailed::class, $this->onJobMarkAsFailed(...));
    }

    /**
     * @param WorkerProcessStarted $event
     * @return void
     */
    private function onWorkerStart(WorkerProcessStarted $event): void
    {
        $this->output->success("Worker started on queue: '{$event->queue}'");
        $this->output->writeln();
    }

    /**
     * @param WorkerAfterMaxJobsStopped $event
     * @return void
     */
    private function onWorkerStopAfterMaxJobProcessed(WorkerAfterMaxJobsStopped $event): void
    {
        $this->output->info("Max jobs reached on queue '{$event->queue}' with {$event->maxJobs} jobs count\n");
        $this->output->info("Worker stopped");
        $this->output->writeln();
    }

    /**
     * @param JobStarted $event
     * @return void
     */
    private function onJobStart(JobStarted $event): void
    {
        $this->output->write("Jobs #{$event->jobEntityId} '{$event->jobClass}'");
        $this->output->badge('doing', Ansi::FG_YELLOW);
    }

    /**
     * @param JobFinished $event
     * @return void
     */
    private function onJobFinish(JobFinished $event): void
    {
        $this->output->write("Jobs #{$event->jobEntityId} '{$event->jobClass}'");
        $this->output->badge('done', Ansi::FG_GREEN);
        $this->output->writeln();
    }

    /**
     * @param JobFailed $event
     * @return void
     */
    private function onJobFail(JobFailed $event): void
    {
        $this->output->write("Jobs #{$event->jobEntityId} '{$event->jobClass}'");
        $this->output->badge('error', Ansi::FG_RED);
        $this->output->writeln();
    }

    /**
     * @param JobRetryScheduled $event
     * @return void
     * @throws DateMalformedStringException
     * @throws JsonException
     */
    private function onJobScheduleToRetry(JobRetryScheduled $event): void
    {
        $jobEntity = $this->jobsRepository->findById(new Ulid($event->jobEntityId));
        if ($jobEntity === null) {
            $this->output->warning("Jobs #{$event->jobEntityId} not found");
            return;
        }

        $job = unserialize($jobEntity->getPayload()['data'], ['allowed_classes' => true]);
        if (!($job instanceof JobInterface)) {
            $this->output->warning("Jobs #{$event->jobEntityId} can not unserialize data");
            return;
        }

        $this->output->warning("Releasing job #{$event->jobEntityId} (attempts {$jobEntity->getAttempts()} / {$job->getMaxAttempts()})");
    }

    /**
     * @param JobCrashed $event
     * @return void
     */
    private function onJobCrash(JobCrashed $event): void
    {
        $this->output->error("Jobs #{$event->jobEntityId} failed because '{$event->exception->getMessage()}'");
        $this->output->writeln();
    }

    /**
     * @param JobRetriesExhausted $event
     * @return void
     */
    private function onJobMaxRetryExhaust(JobRetriesExhausted $event): void
    {
        $this->output->error("Jobs #{$event->jobEntityId} failed after retries {$event->maxAttempts} attempts");
        $this->output->writeln();
    }

    /**
     * @param JobMarkedAsFailed $event
     * @return void
     */
    private function onJobMarkAsFailed(JobMarkedAsFailed $event): void
    {
        $this->output->warning("Jobs #{$event->jobEntityId} moved to failed jobs");
        $this->output->writeln();
    }
}
