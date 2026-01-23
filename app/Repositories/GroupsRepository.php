<?php

namespace App\Repositories;

use App\Entities\Group;
use App\Entities\User;
use App\ValueObjects\Level;
use DateMalformedStringException;
use DomainException;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\Query\QueryMaker;
use Tuto\Utils\DataTransformer;
use Tuto\Utils\ValueObject\Ulid;

class GroupsRepository
{
    use DataTransformer;

    public const array GROUPS_FIELDS = [
        'id',
        'name',
        'level',
        'created_at',
        'updated_at'
    ];

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(private readonly ConnectionInterface $connection)
    {
    }

    /**
     * @param Ulid $id
     * @return Group
     * @throws DateMalformedStringException
     */
    public function getById(Ulid $id): Group
    {
        $groupData = QueryMaker::select(self::GROUPS_FIELDS)
            ->from('`groups`')
            ->where('id', $id)
            ->render()
            ->makeRequest($this->connection)
            ->fetch();

        if ($groupData === false) {
            throw new DomainException("Group with id '{$id}' is not found");
        }

        return $this->fromData($groupData);
    }

    /**
     * @param array $data
     * @return Group
     * @throws DateMalformedStringException
     */
    private function fromData(array $data): Group
    {
        return new Group(
            id: $this->transformToUlid($data['id']),
            name: $data['name'],
            level: new Level($data['level']),
            createdAt: $this->transformToDateTime($data['created_at']),
            updatedAt: $this->transformToDateTime($data['updated_at']),
        );
    }
}