<?php

namespace Tuto\Database\Migrations;

use Tuto\Database\ConnectionInterface;

abstract class Migration
{
    abstract public function up(ConnectionInterface $connection);

    abstract public function down(ConnectionInterface $connection);
}