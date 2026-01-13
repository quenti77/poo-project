<?php

namespace Tuto\Console\Listeners;

use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\Output;
use Tuto\Database\Migrations\Events\MigrationFailed;
use Tuto\Database\Migrations\Events\MigrationFinished;
use Tuto\Database\Migrations\Events\MigrationStarted;
use Tuto\Event\Contract\EventDispatcherInterface;
use Tuto\Event\Contract\EventSubscriberInterface;

class ConsoleMigrationSubscriber implements EventSubscriberInterface
{
    /**
     * @param Output $output
     */
    public function __construct(private readonly Output $output)
    {
    }

    /**
     * @param EventDispatcherInterface $dispatcher
     * @return void
     */
    public function subscribe(EventDispatcherInterface $dispatcher): void
    {
        $dispatcher->listen(MigrationStarted::class, $this->onMigrationStart(...));
        $dispatcher->listen(MigrationFinished::class, $this->onMigrationFinish(...));
        $dispatcher->listen(MigrationFailed::class, $this->onMigrationFail(...));
    }

    /**
     * @param MigrationStarted $event
     * @return void
     */
    private function onMigrationStart(MigrationStarted $event): void
    {
        $this->output->write("Process migration '{$event->migration}' ");
        $this->output->badge("DOING", Ansi::FG_YELLOW);
    }

    /**
     * @param MigrationFinished $event
     * @return void
     */
    private function onMigrationFinish(MigrationFinished $event): void
    {
        $this->output->write("Process migration '{$event->migration}' ");
        $this->output->badge("DONE", Ansi::FG_GREEN);
        $this->output->writeln();
    }

    /**
     * @param MigrationFailed $event
     * @return void
     */
    private function onMigrationFail(MigrationFailed $event): void
    {
        $this->output->write("Process migration '{$event->migration}' ");
        $this->output->badge("ERROR", Ansi::FG_RED);
        $this->output->writeln();

        $this->output->error($event->exception->getMessage());
        $this->output->writeln();
    }

}
