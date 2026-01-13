<?php

namespace Tuto\Database\Migrations\Events;

use Tuto\Event\Event;

class MigrationStarted extends MigrationEvent
{
    /**
     * @param string $migration
     */
    public function __construct(string $migration)
    {
        parent::__construct($migration, MigrationStatus::PROCESSING);
    }
}
