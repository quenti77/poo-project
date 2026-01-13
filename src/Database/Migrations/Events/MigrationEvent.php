<?php

namespace Tuto\Database\Migrations\Events;

use Tuto\Event\Event;

abstract class MigrationEvent extends Event
{
    /**
     * @param string $migration
     * @param MigrationStatus $status
     */
    public function __construct(
        public readonly string $migration,
        public readonly MigrationStatus $status,
    ) {
    }
}
