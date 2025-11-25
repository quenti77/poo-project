<?php

namespace Tuto\Database\Migrations;

use DateMalformedStringException;
use DirectoryIterator;
use InvalidArgumentException;
use IteratorIterator;
use ReflectionException;
use SplFileInfo;
use Throwable;
use Tuto\CLI\Ansi;
use Tuto\CLI\Output;
use Tuto\CLI\Style;
use Tuto\Collection\Collection;
use Tuto\Database\ConnectionInterface;

class MigrationsService
{
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
        $migrationFiles = new Collection();

        $di = new DirectoryIterator(container('path.database') . '/migrations');
        $ii = new IteratorIterator($di);

        /** @var DirectoryIterator $file */
        foreach ($ii as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $migration = $migrated->find(static fn (MigrationEntity $entity) => $entity->getName() === $file->getBasename('.php'));
            if ($migration === null) {
                $migrationFiles->push($file->getFileInfo());
            }
        }

        $newStep = $this->migrationsRepository->getMaxStep() + 1;
        foreach ($migrationFiles as $migration) {
            $baseName = $migration->getBasename('.php');
            $output->write("Process migration '{$baseName}' ");
            $output->block("DOING", Ansi::FG_YELLOW);

            try {
                $this->processMigration($migration, $newStep);

                $output->write("Process migration '{$baseName}' ");
                $output->block("DONE", Ansi::FG_GREEN);
            } catch (Throwable $exception) {
                $output->write("Process migration '{$baseName}' ");
                $output->block("ERROR", Ansi::FG_RED);
                $output->writeln();

                $errorStyle = Style::create()->fgStandard(Ansi::BG_WHITE)->bgStandard(Ansi::BG_RED);
                $output->write($errorStyle->apply($exception->getMessage()));
            }

            $output->writeln();
        }
    }

    private function assertTableExist(Output $output): void
    {
        $migrationExist = $this->migrationsRepository->assertExist();
        if (!$migrationExist) {
            $output->styled("Migration table does not exist. Creating ...", Style::create()->fgStandard(Ansi::FG_YELLOW));
            $this->migrationsRepository->createMigrationTable();
            $output->styled("Migration table created", Style::create()->fgStandard(Ansi::FG_GREEN));
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
        if (!($instance instanceof Migration)) {
            throw new InvalidArgumentException($instance::class . " must be an instance of " . Migration::class);
        }

        $connection = $this->migrationsRepository->connection;
        try {
            $connection->beginTransaction();
            $instance->up($connection);

            $this->migrationsRepository->create($migration, $step);
            $connection->commit();
        } catch (Throwable $exception) {
            $connection->rollback();
            throw $exception;
        }
    }
}
