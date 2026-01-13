<?php

namespace Tuto\Database\Migrations;

use DateMalformedStringException;
use DirectoryIterator;
use InvalidArgumentException;
use IteratorIterator;
use ReflectionException;
use SplFileInfo;
use Throwable;
use Tuto\Database\Migrations\Events\MigrationFailed;
use Tuto\Database\Migrations\Events\MigrationFinished;
use Tuto\Database\Migrations\Events\MigrationStarted;

class MigrationsService
{
    /**
     * @param MigrationsRepository $migrationsRepository
     */
    public function __construct(private readonly MigrationsRepository $migrationsRepository)
    {
    }

    /**
     * @return void
     * @throws DateMalformedStringException
     * @throws ReflectionException
     */
    public function up(): void
    {
        $this->assertTableExist();

        $migrated = $this->migrationsRepository->all();
        $migrationFiles = collect();

        $di = new DirectoryIterator(container('path.database') . '/migrations');
        $ii = new IteratorIterator($di);

        /** @var DirectoryIterator $file */
        foreach ($ii as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $migration = $migrated->find(static fn ($key, MigrationEntity $entity) => $entity->getName() === $file->getBasename('.php'));
            if ($migration === null) {
                $migrationFiles->push($file->getFileInfo());
            }
        }

        $newStep = $this->migrationsRepository->getMaxStep() + 1;
        foreach ($migrationFiles as $migration) {
            $baseName = $migration->getBasename('.php');
            event(new MigrationStarted($baseName));

            try {
                $this->processMigration($migration, $newStep);
                event(new MigrationFinished($baseName));
            } catch (Throwable $exception) {
                event(new MigrationFailed($baseName, $exception));
            }
        }
    }

    /**
     * @return void
     * @throws DateMalformedStringException
     */
    public function down(): void
    {
        $this->assertTableExist();

        $currentStep = $this->migrationsRepository->getMaxStep();
        $migrated = $this->migrationsRepository->latestMigrations($currentStep);

        /** @var MigrationEntity $migration */
        foreach ($migrated as $migration) {
            $baseName = $migration->getName();
            event(new MigrationStarted($baseName));

            try {
                $this->rollbackMigration($migration);
                event(new MigrationFinished($baseName));
            } catch (Throwable $exception) {
                event(new MigrationFailed($baseName, $exception));
            }
        }
    }

    private function assertTableExist(): void
    {
        $migrationExist = $this->migrationsRepository->assertExist();
        if (!$migrationExist) {
            $baseName = 'create_migrations_table';
            event(new MigrationStarted($baseName));

            try {
                $this->migrationsRepository->createMigrationTable();
                event(new MigrationFinished($baseName));
            } catch (Throwable $exception) {
                event(new MigrationFailed($baseName, $exception));
            }
        }
    }

    /**
     * @param SplFileInfo $migration
     * @param int $step
     * @return void
     * @throws Throwable
     */
    private function processMigration(SplFileInfo $migration, int $step): void
    {
        $instance = require $migration->getRealPath();
        if (!($instance instanceof MigrationInterface)) {
            throw new InvalidArgumentException($instance::class . " must be an instance of " . MigrationInterface::class);
        }

        $connection = $this->migrationsRepository->connection;
        try {
            $connection->startTransaction();
            $instance->up($connection);

            $this->migrationsRepository->create($migration, $step);
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }

    /**
     * @param MigrationEntity $migrationEntity
     * @return void
     * @throws Throwable
     */
    private function rollbackMigration(MigrationEntity $migrationEntity): void
    {
        $instance = require $migrationEntity->getFile()->getRealPath();
        if (!($instance instanceof MigrationInterface)) {
            throw new InvalidArgumentException($instance::class . " must be an instance of " . MigrationInterface::class);
        }

        $connection = $this->migrationsRepository->connection;
        try {
            $connection->startTransaction();
            $instance->down($connection);

            $this->migrationsRepository->delete($migrationEntity);
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }
}
