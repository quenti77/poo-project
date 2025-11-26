<?php

namespace Tuto\Database\Migrations;

use App\Repositories\DataTransformer;
use App\ValueObjects\Ulid;
use DateMalformedStringException;
use Exception;
use ReflectionException;
use SplFileInfo;
use Tuto\Collection\Collection;
use Tuto\Database\ConnectionInterface;

class MigrationsRepository
{
    use DataTransformer;

    public function __construct(public readonly ConnectionInterface $connection)
    {
    }

    public function assertExist(): bool
    {
        $result = $this->connection->request("show tables like 'migrations'")->fetch();
        return $result !== false;
    }

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
     * @throws ReflectionException
     */
    public function all(): Collection
    {
        $request = $this->connection->request('select id, name, step, created_at from migrations order by step');

        $migrations = new Collection();
        while ($migrationData = $request->fetch()) {
            $migrations->push($this->dataToEntity($migrationData));
        }
        return $migrations;
    }

    /**
     * @return Collection<int, MigrationEntity>
     * @throws DateMalformedStringException
     * @throws ReflectionException
     */
    public function latestMigrations(int|null $step = null): Collection
    {
        $request = $this->connection->request('select id, name, step, created_at from migrations where step >= :step order by id desc', [
            ':step' => $step ?? $this->getMaxStep(),
        ]);

        $migrations = new Collection();
        while ($migrationData = $request->fetch()) {
            $migrations->push($this->dataToEntity($migrationData));
        }
        return $migrations;
    }

    public function getMaxStep(): int
    {
        return (int) $this->connection
            ->request('select max(step) as max_step from migrations')
            ->fetch()['max_step'];
    }

    /**
     * @param SplFileInfo $migration
     * @param int $step
     * @return void
     * @throws Exception
     */
    public function create(SplFileInfo $migration, int $step): void
    {
        $this->connection->request(
            'insert into migrations (id, name, step, created_at) values (:id, :name, :step, NOW());',
            [
                ':id' => Ulid::next()->value,
                ':name' => $migration->getBasename(".{$migration->getExtension()}"),
                ':step' => $step,
            ],
        );
    }

    public function delete(MigrationEntity $migration): void
    {
        $this->connection->request('delete from migrations where id = :id', [':id' => $migration->getId()]);
    }

    /**
     * @param array $data
     * @return MigrationEntity
     * @throws DateMalformedStringException
     * @throws ReflectionException
     */
    private function dataToEntity(array $data): MigrationEntity
    {
        return new MigrationEntity(
            id: new Ulid($data['id']),
            file: new SplFileInfo(container('path.database') . "/migrations/{$data['name']}.php"),
            step: (int) $data['step'],
            createAt: $this->transformToDateTime($data['created_at']),
        );
    }
}
