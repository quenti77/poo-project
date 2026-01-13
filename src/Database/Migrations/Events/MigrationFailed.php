<?php

namespace Tuto\Database\Migrations\Events;

use Throwable;

class MigrationFailed extends MigrationEvent
{
    /**
     * @param string $migration
     * @param Throwable $exception
     */
    public function __construct(string $migration, public readonly Throwable $exception)
    {
        parent::__construct($migration, MigrationStatus::ERROR);
    }
}
