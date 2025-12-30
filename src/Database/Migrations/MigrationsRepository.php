<?php

namespace Tuto\Database\Migrations;

use DateMalformedStringException;
use Random\RandomException;
use SplFileInfo;
use Tuto\Collections\Collection;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\DatabaseRepositoryInterface;
use Tuto\Database\Query\QueryBuilder;
use Tuto\Database\Query\QueryMaker;
use Tuto\Utils\DataTransformer;
use Tuto\Utils\Ulid;

class MigrationsRepository implements DatabaseRepositoryInterface
{
    use DataTransformer;

    public const array FIELDS = ['id', 'name', 'step', 'created_at'];

    /**
     * @param ConnectionInterface $connection
     */
    public function __construct(public readonly ConnectionInterface $connection)
    {
    }

    /**
     * @return bool
     */
    public function assertExist(): bool
    {
        $result = $this->connection->request("show tables like 'migrations'")->fetch();
        return $result !== false;
    }

    /**
     * @return void
     */
    public function createMigrationTable(): void
    {
        $createStatement = <<<EOS
        create table migrations
        (
            id varchar(32) not null primary key,
            name varchar(255) not null,
            step int not null,
            created_at datetime not null
        );
        EOS;

        $this->connection->request($createStatement);
        $this->connection->request("create index migrations_step_index on migrations (step);");
    }

    /**
     * @return Collection<int, MigrationEntity>
     * @throws DateMalformedStringException
     */
    public function all(): Collection
    {
        $request = $this->createRequest()
            ->orderBy('step')
            ->render()
            ->makeRequest($this->connection);

        $migrations = collect();
        while ($migrationData = $request->fetch()) {
            $migrations->push($this->dataToEntity($migrationData));
        }
        return $migrations;
    }

    /**
     * @return Collection<int, MigrationEntity>
     * @throws DateMalformedStringException
     */
    public function latestMigrations(int|null $step = null): Collection
    {
        $request = $this->createRequest()
            ->where('step', '>=', $step ?? $this->getMaxStep())
            ->orderByDesc('id')
            ->render()
            ->makeRequest($this->connection);

        $migrations = collect();
        while ($migrationData = $request->fetch()) {
            $migrations->push($this->dataToEntity($migrationData));
        }
        return $migrations;
    }

    /**
     * @return int
     */
    public function getMaxStep(): int
    {
        return (int)$this->connection
            ->request('select max(step) as max_step from migrations')
            ->fetch()['max_step'];
    }

    /**
     * @param SplFileInfo $migration
     * @param int $step
     * @return void
     * @throws RandomException
     */
    public function create(SplFileInfo $migration, int $step): void
    {
        $values = [
            'id' => Ulid::next()->value,
            'name' => $migration->getBasename(".{$migration->getExtension()}"),
            'step' => $step,
        ];
        QueryMaker::insert('migrations')
            ->values($values)
            ->value('created_at', 'NOW()', false)
            ->render()
            ->makeRequest($this->connection);
    }

    /**
     * @param MigrationEntity $migration
     * @return void
     */
    public function delete(MigrationEntity $migration): void
    {
        QueryMaker::delete('migrations')
            ->where('id', (string)$migration->getId())
            ->render()
            ->makeRequest($this->connection);
    }

    /**
     * @return QueryBuilder
     */
    private function createRequest(): QueryBuilder
    {
        return QueryMaker::select(static::FIELDS)->from('migrations');
    }

    /**
     * @param array $data
     * @return MigrationEntity
     * @throws DateMalformedStringException
     */
    private function dataToEntity(array $data): MigrationEntity
    {
        return new MigrationEntity(
            id: new Ulid($data['id']),
            file: new SplFileInfo(container('path.database') . "/migrations/{$data['name']}.php"),
            step: (int)$data['step'],
            createAt: $this->transformToDateTime($data['created_at']),
        );
    }
}