<?php

namespace Tuto\Console\Commands\Database;

use InvalidArgumentException;
use Throwable;
use Tuto\Base\ClassNotFoundException;
use Tuto\Collections\Collection;
use Tuto\Console\Commands\AbstractCommand;
use Tuto\Console\Commands\CommandStatus;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;
use Tuto\Database\Seeders\SeederRunner;

class DbSeedCommand extends AbstractCommand
{
    /**
     * @param SeederRunner $seederRunner
     */
    public function __construct(private readonly SeederRunner $seederRunner)
    {
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return "db:seed";
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return "Seed the database with records";
    }

    /**
     * @return Collection<string, string>
     */
    public function getArguments(): Collection
    {
        return collect([
            'seeder' => 'Database\\Seeders\\DatabaseSeeder',
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function getExamples(): Collection
    {
        return collect([
            'php cli db:seed',
            'php cli db:seed Database\\Seeders\\UserSeeder',
            'php cli db:seed Database\\Seeders\\LocalSeeder',
        ]);
    }

    /**
     * @param Input $input
     * @param Output $output
     * @return CommandStatus
     * @throws Throwable
     */
    public function execute(Input $input, Output $output): CommandStatus
    {
        $seederClass = $input->getArgument(0, 'Database\\Seeders\\DatabaseSeeder');

        try {
            container($seederClass);
        } catch (ClassNotFoundException) {
            $output->error("Seeder class not found: {$seederClass}");
            return CommandStatus::GENERIC_FAILURE;
        }

        $this->seederRunner->runSeeder($seederClass);

        $output->writeln();
        $output->success("Database seeded successfully!");

        return CommandStatus::SUCCESS;
    }
}
