<?php

namespace Tuto\Console\Commands\Queue;

use DateMalformedStringException;
use JsonException;
use Random\RandomException;
use Tuto\Collections\Collection;
use Tuto\Console\Commands\AbstractCommand;
use Tuto\Console\Commands\CommandStatus;
use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;
use Tuto\Console\Components\Output\Borders\OutputBorder;
use Tuto\Console\Components\Output\Table\Column;
use Tuto\Queue\FailedJobsRepository;
use Tuto\Queue\JobsRepository;

class QueueStatsCommand extends AbstractCommand
{
    /**
     * @param JobsRepository $jobsRepository
     * @param FailedJobsRepository $failedJobsRepository
     */
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
        return "queue:stats";
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return "Display statistics for a specific queue";
    }

    /**
     * @return Collection<string, string|null>
     */
    public function getArguments(): Collection
    {
        return parent::getArguments()->merge(['queue' => 'default']);
    }

    /**
     * @return Collection<int, string>
     */
    public function getExamples(): Collection
    {
        return parent::getExamples()->merge([
            'queue:stats',
            'queue:stats mails',
        ]);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return CommandStatus
     */
    public function execute(Input $input, Output $output): CommandStatus
    {
        $queue = $input->getArgument(0, 'default');

        $stats = $this->jobsRepository->getStats($queue);
        $stats->setFailed($this->failedJobsRepository->count($queue));

        $output->blockInfo("Queue: {$queue}");
        $output->writeln();

        $items = collect();
        $items->push(['Total jobs', $stats->total]);
        $items->push(['Pending jobs', $stats->pending]);
        $items->push(['Delayed jobs', $stats->delayed]);
        $items->push(['Reserved jobs', $stats->reserved]);
        $items->push(['Failed jobs', $stats->getFailed()]);

        $output->table($items)
            ->addColumn(Column::make('Label'))
            ->addColumn(Column::make('Nb of items'))
            ->withRowColorizer(static function ($item, int $rowIndex): Ansi {
                return match($item[0]) {
                    'Total jobs', 'Pending jobs' => Ansi::FG_CYAN,
                    'Delayed jobs' => Ansi::FG_GREEN,
                    'Reserved jobs' => $item[1] > 0 ? Ansi::FG_YELLOW : Ansi::FG_GREEN,
                    'Failed jobs' => $item[1] > 0 ? Ansi::FG_RED : Ansi::FG_GREEN,
                };
            })
            ->withBorder(OutputBorder::SIMPLE)
            ->run();

        return CommandStatus::SUCCESS;
    }
}
