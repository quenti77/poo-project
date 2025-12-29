<?php

namespace Tuto\Database\Migrations;

use DateMalformedStringException;
use DirectoryIterator;
use InvalidArgumentException;
use IteratorIterator;
use ReflectionException;
use SplFileInfo;
use Throwable;
use Tuto\CLI\Output\Ansi;
use Tuto\CLI\Output\Output;

class MigrationsService
{
    /**
     * @param MigrationsRepository $migrationsRepository
     */
    public function __construct(private readonly MigrationsRepository $migrationsRepository)
    {
    }

    /**
     * @param Output $output
     * @return void
     * @throws DateMalformedStringException
     * @throws ReflectionException
     */
    public function up(Output $output): void
    {
        $this->assertTableExist($output);

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
            $output->write("Process migration '{$baseName}' ");
            $output->badge("DOING", Ansi::FG_YELLOW);

            try {
                $this->processMigration($migration, $newStep);

                $output->write("Process migration '{$baseName}' ");
                $output->badge("DONE", Ansi::FG_GREEN);
            } catch (Throwable $exception) {
                $output->write("Process migration '{$baseName}' ");
                $output->badge("ERROR", Ansi::FG_RED);
                $output->writeln();

                $output->error($exception->getMessage());
            }

            $output->writeln();
        }
    }

    /**
     * @param Output $output
     * @return void
     * @throws DateMalformedStringException
     */
    public function down(Output $output): void
    {
        $this->assertTableExist($output);

        $currentStep = $this->migrationsRepository->getMaxStep();
        $migrated = $this->migrationsRepository->latestMigrations($currentStep);

        /** @var MigrationEntity $migration */
        foreach ($migrated as $migration) {
            $baseName = $migration->getName();
            $output->write("Process migration '{$baseName}' ");
            $output->badge("DOING", Ansi::FG_YELLOW);

            try {
                $this->rollbackMigration($migration);

                $output->write("Process migration '{$baseName}' ");
                $output->badge("DONE", Ansi::FG_GREEN);
            } catch (Throwable $exception) {
                $output->write("Process migration '{$baseName}' ");
                $output->badge("ERROR", Ansi::FG_RED);
                $output->writeln();

                $output->error($exception->getMessage());
            }

            $output->writeln();
        }
    }

    private function assertTableExist(Output $output): void
    {
        $migrationExist = $this->migrationsRepository->assertExist();
        if (!$migrationExist) {
            $output->warning("Migration table does not exist. Creating ...");
            $this->migrationsRepository->createMigrationTable();
            $output->success("Migration table created");
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
