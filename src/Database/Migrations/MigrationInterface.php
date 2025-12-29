<?php

namespace Tuto\Database\Migrations;

use Tuto\Database\ConnectionInterface;

interface MigrationInterface
{
    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function up(ConnectionInterface $connection): void;

    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function down(ConnectionInterface $connection): void;
}