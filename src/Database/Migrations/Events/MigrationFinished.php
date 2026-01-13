<?php

namespace Tuto\Database\Migrations\Events;

class MigrationFinished extends MigrationEvent
{
    /**
     * @param string $migration
     */
    public function __construct(string $migration)
    {
        parent::__construct($migration, MigrationStatus::FINISHED);
    }
}
