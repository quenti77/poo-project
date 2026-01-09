<?php

namespace Tuto\Console\Commands\Queue;

use DateMalformedStringException;
use JsonException;
use Random\RandomException;
use Tuto\Collections\Collection;
use Tuto\Console\Commands\AbstractCommand;
use Tuto\Console\Commands\CommandStatus;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;
use Tuto\Queue\FailedJobsRepository;
use Tuto\Queue\JobsRepository;

class QueueRetryCommand extends AbstractCommand
{
    public function __construct(
        private readonly JobsRepository $jobsRepository,
        private readonly FailedJobsRepository $failedJobsRepository,
    ) {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "queue:retry";
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return "Retry failed jobs";
    }

    /**
     * @return Collection<string, string|null>
     */
    public function getArguments(): Collection
    {
        return parent::getArguments()->merge(['queue' => 'default']);
    }

    /**
     * @return Collection<string, string|bool>
     */
    public function getOptions(): Collection
    {
        return parent::getOptions()->merge([
            'id' => 'ulid of specific job',
            'limit' => '100',
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function getExamples(): Collection
    {
        return parent::getExamples()->merge([
            'queue:retry',
            'queue:retry emails',
            'queue:retry default --id=ULID',
            'queue:retry default --limit=10',
        ]);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return CommandStatus
     * @throws DateMalformedStringException
     * @throws JsonException
     * @throws RandomException
     */
    public function execute(Input $input, Output $output): CommandStatus
    {
        $queue = $input->getArgument(0, 'default');

        $id = $input->getOption('id');
        $limit = (int) $input->getOption('limit', 100);

        if (!empty($id)) {
            $limit = 1;
        }

        if ($limit < 1) {
            $output->error("Invalid limit value '{$limit}', limit must be greater than 0");
            $output->writeln();
            return CommandStatus::GENERIC_FAILURE;
        }

        $failedJobs = $this->failedJobsRepository->getJobs($queue, $id, $limit);
        if ($failedJobs->isEmpty()) {
            $output->info("No failed jobs found in queue '{$queue}'");
            $output->writeln();
            return CommandStatus::SUCCESS;
        }

        $this->jobsRepository->retryJobs($failedJobs);
        $this->failedJobsRepository->deleteJobs($failedJobs);

        $output->success("Retry {$failedJobs->count()} failed jobs");
        $output->writeln();

        return CommandStatus::SUCCESS;
    }
}
