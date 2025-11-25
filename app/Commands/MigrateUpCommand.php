<?php

namespace App\Commands;

use Tuto\CLI\Command;
use Tuto\CLI\Input;
use Tuto\CLI\Output;
use Tuto\Database\Migrations\MigrationsService;

class MigrateUpCommand extends Command
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

    public function execute(Input $input, Output $output): int
    {
        $this->migrationsService->up($output);
        return self::EXIT_SUCCESS;
    }
}