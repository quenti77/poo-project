<?php

namespace App\Commands\Migrate;

use DateMalformedStringException;
use ReflectionException;
use Tuto\CLI\Command;
use Tuto\CLI\Input\Input;
use Tuto\CLI\Output\Output;
use Tuto\Database\Migrations\MigrationsService;

class MigrationUpCommand extends Command
{
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
     * @return int
     * @throws DateMalformedStringException
     * @throws ReflectionException
     */
    public function execute(Input $input, Output $output): int
    {
        $this->migrationsService->up($output);
        return self::EXIT_SUCCESS;
    }
}