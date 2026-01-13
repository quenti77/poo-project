<?php

namespace Tuto\Console\Commands\Migrate;

use DateMalformedStringException;
use ReflectionException;
use Tuto\Console\Commands\AbstractCommand;
use Tuto\Console\Commands\CommandStatus;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;
use Tuto\Database\Migrations\MigrationsService;

class MigrationUpCommand extends AbstractCommand
{
    /**
     * @param MigrationsService $migrationsService
     */
    public function __construct(private readonly MigrationsService $migrationsService)
    {
    }

    public function getName(): string
    {
        return "migrate:up";
    }

    public function getDescription(): string
    {
        return "Migrate the database to the last migration can be run";
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return CommandStatus
     * @throws DateMalformedStringException
     * @throws ReflectionException
     */
    public function execute(Input $input, Output $output): CommandStatus
    {
        $this->migrationsService->up();
        return CommandStatus::SUCCESS;
    }
}
