<?php

namespace Database\Seeders\Local;

use InvalidArgumentException;
use Random\RandomException;
use Throwable;
use Tuto\Database\Query\QueryMaker;
use Tuto\Database\Seeders\AbstractSeeder;
use Tuto\Utils\Hash;
use Tuto\Utils\ValueObject\Ulid;

class UserSeeder extends AbstractSeeder
{
    public const string MEMBER_ID = '01KFDE5X52TCF4VM59QYEMAYG1';
    public const string ADMIN_ID = '01KFDE60WYZ45TP5XH1238SGD6';

    /**
     * @return void
     * @throws Throwable
     */
    public function run(): void
    {
        $this->createUser([
            'id' => self::MEMBER_ID,
            'group_id' => GroupSeeder::MEMBER_GROUP_ID,
            'name' => 'member',
        ]);
        $this->createUser([
            'id' => self::ADMIN_ID,
            'group_id' => GroupSeeder::ADMIN_GROUP_ID,
            'name' => 'admin',
        ]);
    }

    /**
     * @param array $data
     * @return void
     * @throws RandomException
     */
    private function createUser(array $data): void
    {
        $id = $data['id'] ?? throw new InvalidArgumentException("Missing id");
        $groupId = $data['group_id'] ?? throw new InvalidArgumentException("Missing group_id");

        $now = $this->currentTime->now();

        $username = $data['name'] ?? 'user_' . random_int(1_000, 10_000);
        $email = $data['email'] ?? $username . '@poo.project';
        $password = $data['password'] ?? Hash::make('password');

        $data = [
            'id' => $id,
            'group_id' => new Ulid($groupId),
            'name' => $username,
            'email' => $email,
            'email_verified_at' => $now,
            'password' => $password,
            'token' => null,
            'created_at' => $now,
            'updated_at' => $now,
            ...$data,
        ];

        QueryMaker::insert('users')
            ->values($data)
            ->render()
            ->makeRequest($this->connection);
    }

}