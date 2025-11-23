<?php

namespace App\Commands;

use Tuto\Base\Command;

class MigrationUpCommand extends Command
{
    public string $name = 'migration:up';
    public string $description = 'Forward the migration to the head';

    public function arguments(): array
    {
        return [];
    }

    public function run(array $arguments): int
    {
        return self::EXIT_SUCCESS;
    }
}