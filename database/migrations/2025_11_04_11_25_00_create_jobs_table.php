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
        $createJobsTableRequest = <<<EOS
        create table `jobs` (
            `id` varchar(32) not null,
            `queue` varchar(255) not null,
            `payload` json not null,
            `attempts` smallint unsigned not null,
            `reserved_at` datetime,
            `available_at` datetime not null,
            `created_at` datetime not null,
            `updated_at` datetime not null,
            primary key (`id`)
        ) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;
        EOS;
        $connection->request($createJobsTableRequest);
        $connection->request("create index idx_jobs_queue_reserved_available on jobs(queue, reserved_at, available_at)");

        $createFailedJobsTable = <<<EOS
        create table `failed_jobs` (
            `id` varchar(32) not null,
            `queue` varchar(255) not null,
            `payload` json not null,
            `exception` text not null,
            `failed_at` datetime not null,
            primary key (`id`)
        ) engine=innodb default charset=utf8mb4 collate=utf8mb4_0900_ai_ci;
        EOS;
        $connection->request($createFailedJobsTable);
        $connection->request("create index idx_failed_jobs_queue on failed_jobs(queue);");
    }

    /**
     * @param ConnectionInterface $connection
     * @return void
     */
    public function down(ConnectionInterface $connection): void
    {
        $connection->request("drop index idx_failed_jobs_queue on failed_jobs");
        $connection->request("drop table if exists `failed_jobs`;");

        $connection->request("drop index idx_jobs_queue_reserved_available on jobs");
        $connection->request("drop table if exists `jobs`;");
    }
};
