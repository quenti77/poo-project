<?php

namespace App\Commands;

use DateMalformedStringException;
use ReflectionException;
use Tuto\CLI\Command;
use Tuto\CLI\Input;
use Tuto\CLI\Output;
use Tuto\Database\Migrations\MigrationsService;

class MigrateDownCommand extends Command
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
     * @throws ReflectionException
     */
    public function execute(Input $input, Output $output): int
    {
        $this->migrationsService->down($output);
        return self::EXIT_SUCCESS;
    }
}