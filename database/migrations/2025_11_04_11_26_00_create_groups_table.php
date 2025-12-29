<?php

use Tuto\Database\ConnectionInterface;
use Tuto\Database\Migrations\MigrationInterface;

return new class implements MigrationInterface
{
    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function up(ConnectionInterface $connection): void
    {
        $createRequest = <<<EOS
        create table `groups` (
            `id` varchar(32) not null,
            `name` varchar(255) not null,
            `level` int unsigned not null,
            `created_at` datetime not null,
            `updated_at` datetime not null,
            primary key (`id`)
        ) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;
        EOS;
        $connection->request($createRequest);
    }

    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function down(ConnectionInterface $connection): void
    {
        $connection->request("drop table if exists `groups`;");
    }
};
