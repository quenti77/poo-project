<?php

namespace App\Commands\Migrate;

use DateMalformedStringException;
use Tuto\CLI\Command;
use Tuto\CLI\Input\Input;
use Tuto\CLI\Output\Output;
use Tuto\Database\Migrations\MigrationsService;

class MigrationDownCommand extends Command
{
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
     * @return int
     * @throws DateMalformedStringException
     */
    public function execute(Input $input, Output $output): int
    {
        $this->migrationsService->down($output);
        return self::EXIT_SUCCESS;
    }
}