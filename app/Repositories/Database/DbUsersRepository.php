<?php

namespace App\Repositories\Database;

use App\Entities\Group;
use App\Entities\User;
use App\Repositories\Contracts\UsersRepositoryInterface;
use App\Repositories\DataTransformer;
use App\ValueObjects\Email;
use App\ValueObjects\Ulid;
use DateMalformedStringException;
use Tuto\Database\ConnectionInterface;

class DbUsersRepository implements UsersRepositoryInterface
{
    use DataTransformer;

    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getActiveUsers(): array
    {
        $userRequest = $this->connection->request(
            "SELECT {$this->getFields()}
            FROM users AS u
            INNER JOIN `groups` AS g ON g.id = u.group_id
            WHERE u.email_verified_at is not null",
        );

        $users = [];
        while ($userData = $userRequest->fetch()) {
            $users[] = $this->toEntity($userData);
        }
        return $users;
    }

    /**
     * @throws DateMalformedStringException
     */
    public function getById(Ulid $userId): User
    {
        $userRequest = $this->connection->request(
            "SELECT {$this->getFields()}
            FROM users AS u
            INNER JOIN `groups` AS g ON g.id = u.group_id
            WHERE u.id = :userId",
            [':userId' => $userId]
        );
        return $this->toEntity($userRequest->fetch());
    }

    /**
     * @throws DateMalformedStringException
     */
    private function toEntity(array $userData): User
    {
        $group = new Group(
            id: new Ulid($userData['g_id']),
            name: $userData['g_name'],
            level: $userData['g_level'],
            createdAt: $this->transformToDateTime($userData['g_created_at']),
            updatedAt: $this->transformToDateTime($userData['g_updated_at']),
        );
        return new User(
            id: new Ulid($userData['u_id']),
            group: $group,
            name: $userData['u_name'],
            email: Email::makeByAddress($userData['u_email']),
            emailVerifiedAt: $this->transformToDateTimeOrNull($userData['u_email_verified_at']),
            password: $userData['u_password'],
            token: $userData['u_token'],
            createdAt: $this->transformToDateTime($userData['u_created_at']),
            updatedAt: $this->transformToDateTime($userData['u_updated_at']),
        );
    }

    private function getFields(): string
    {
        return 'u.id as u_id, u.group_id as u_group_id, u.name as u_name,
            u.email as u_email, u.email_verified_at as u_email_verified_at, u.password as u_password,
            u.token as u_token, u.created_at as u_created_at, u.updated_at as u_updated_at,
            g.id as g_id, g.name as g_name, g.level as g_level, g.created_at as g_created_at, g.updated_at as g_updated_at';
    }
}