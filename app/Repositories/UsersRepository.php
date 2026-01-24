<?php

namespace App\Repositories;

use App\Entities\User;
use App\ValueObjects\Email;
use App\ValueObjects\PasswordHashed;
use DateMalformedStringException;
use DomainException;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\Query\QueryMaker;
use Tuto\Utils\DataTransformer;
use Tuto\Utils\ValueObject\Ulid;

class UsersRepository
{
    use DataTransformer;

    public const array USERS_FIELDS = [
        'id',
        'group_id',
        'name',
        'email',
        'email_verified_at',
        'password',
        'token',
        'created_at',
        'updated_at'
    ];

    /**
     * @param GroupsRepository $groupsRepository
     * @param ConnectionInterface $connection
     */
    public function __construct(
        private readonly GroupsRepository $groupsRepository,
        private readonly ConnectionInterface $connection,
    ) {
    }

    /**
     * @param Ulid $id
     * @return User
     * @throws DateMalformedStringException
     */
    public function getById(Ulid $id): User
    {
        $userData = QueryMaker::select(self::USERS_FIELDS)
            ->from('users')
            ->where('id', $id)
            ->render()
            ->makeRequest($this->connection)
            ->fetch();

        if ($userData === false) {
            throw new DomainException("User with id '{$id}' is not found");
        }

        return $this->fromData($userData);
    }

    /**
     * @param array $data
     * @return User
     * @throws DateMalformedStringException
     */
    private function fromData(array $data): User
    {
        $group = $data['group'] ?? $this->groupsRepository->getById($this->transformToUlid($data['group_id']));

        return new User(
            id: $this->transformToUlid($data['id']),
            group: $group,
            name: $data['name'],
            email: Email::fromString($data['email']),
            emailVerifiedAt: $this->transformToDateTimeOrNull($data['email_verified_at']),
            password: new PasswordHashed($data['password']),
            token: $data['token'] ?? null,
            createdAt: $this->transformToDateTime($data['created_at']),
            updatedAt: $this->transformToDateTime($data['updated_at']),
        );
    }
}