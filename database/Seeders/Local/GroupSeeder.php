<?php

namespace Database\Seeders\Local;

use Random\RandomException;
use Throwable;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\Query\QueryMaker;
use Tuto\Database\Seeders\AbstractSeeder;
use Tuto\Utils\CurrentTime;
use Tuto\Utils\ValueObject\Ulid;

class GroupSeeder extends AbstractSeeder
{
    public const string MEMBER_GROUP_ID = '01KFDDTFC0Y4RKCDP2A76V11HH';
    public const string ADMIN_GROUP_ID = '01KFDDTN6P9850ZC6VCRCCV299';

    /**
     * @return void
     * @throws Throwable
     */
    public function run(): void
    {
        $this->createGroup(new Ulid(self::MEMBER_GROUP_ID), 'member', 10);
        $this->createGroup(new Ulid(self::ADMIN_GROUP_ID), 'admin', 100);
    }

    /**
     * @param Ulid $id
     * @param string $name
     * @param int $level
     * @return void
     */
    private function createGroup(Ulid $id, string $name, int $level): void
    {
        $now = $this->currentTime->now();

        QueryMaker::insert('`groups`')
            ->values([
                'id' => $id,
                'name' => $name,
                'level' => $level,
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->render()
            ->makeRequest($this->connection);
    }

}