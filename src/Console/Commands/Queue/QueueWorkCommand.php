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
use Tuto\Queue\WorkerService;

class QueueWorkCommand extends AbstractCommand
{
    public function __construct(private readonly WorkerService $workerService)
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "queue:work";
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return "Process jobs on the selected queue";
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
            'max-jobs' => '0',
            'sleep' => '3',
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function getExamples(): Collection
    {
        return parent::getExamples()->merge([
            'queue:work',
            'queue:work mails',
            'queue:work billings --max-jobs=10',
            'queue:work mails --sleep=5',
            'queue:work default --max-jobs=100 --sleep=10',
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
        $maxJobs = (int) $input->getOption('max-jobs', 0);
        $sleep = (int) $input->getOption('sleep', 3);

        $this->workerService->work($queue, $maxJobs, $sleep);
        return CommandStatus::SUCCESS;
    }
}
