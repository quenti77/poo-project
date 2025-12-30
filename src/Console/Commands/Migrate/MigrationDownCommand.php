<?php

namespace Tuto\Console\Commands\Migrate;

use DateMalformedStringException;
use Tuto\Console\Commands\AbstractCommand;
use Tuto\Console\Commands\CommandStatus;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;
use Tuto\Database\Migrations\MigrationsService;

class MigrationDownCommand extends AbstractCommand
{
    /**
     * @param MigrationsService $migrationsService
     */
    public function __construct(private readonly MigrationsService $migrationsService)
    {
    }

    public function getName(): string
    {
        return "migrate:down";
    }

    public function getDescription(): string
    {
        return "Revert latest migration of the database";
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return CommandStatus
     * @throws DateMalformedStringException
     */
    public function execute(Input $input, Output $output): CommandStatus
    {
        $this->migrationsService->down($output);
        return CommandStatus::SUCCESS;
    }
}