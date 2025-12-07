<?php

namespace Tuto\CLI;

use ReflectionException;
use Throwable;
use Tuto\Base\ApplicationConfigurable;
use Tuto\Collection\Collection;

class ConsoleApplication
{
    use ApplicationConfigurable;

    private Output $output;

    /** @var Collection<int, Command> */
    private Collection $commands;

    public function __construct(string $configPath, Output $output)
    {
        $this->configPath = $configPath;
        $this->output = $output;
        $this->commands = collect();

        try {
            $this->initConfig();
        } catch (ReflectionException) {
        }
    }

    public function addCommand(string $command): self
    {
        try {
            return $this->addCommandInstance(container($command));
        } catch (ReflectionException) {
            $this->output->writeln("Cannot add this command : '{$command}'");
            return $this;
        }
    }

    public function addCommandInstance(Command $command): self
    {
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    public function find(string $name): Command|null
    {
        return $this->commands[$name] ?? null;
    }

    public function run(Input $input): int
    {
        $commandName = $input->getCommandName();
        if ($commandName === null || $commandName === 'help') {
            $this->runHelp();
            return Command::EXIT_SUCCESS;
        }

        $command = $this->find($commandName);
        if ($command === null) {
            $this->output->error("Command not found '{$commandName}'");
            $this->runHelp();
            return Command::EXIT_FAILURE;
        }

        try {
            return $command->execute($input, $this->output);
        } catch (Throwable $exception) {
            $this->output->error($exception->getMessage());
            return Command::EXIT_FAILURE;
        }
    }

    private function runHelp(): void
    {
        $this->output->successBlock("Available commands");

        $rows = $this->commands
            ->map(static fn ($key, Command $c) => [$c->getName(), $c->getDescription()])
            ->all();
        $rows[] = ['help', 'Get the list of available commands'];
        usort($rows, static fn (array $a, array $b) => strcmp($a[0], $b[0]));

        $max = 0;
        $groups = [];
        foreach ($rows as $row) {
            $max = max($max, strlen($row[0]));

            $parts = explode(':', $row[0]);
            array_pop($parts);

            $groupName = implode(':', $parts + ['__global']);
            $groups[$groupName][] = $row;
        }

        foreach ($groups as $groupName => $commands) {
            $this->output->styled("{$groupName}:\n", Style::warning());
            foreach ($commands as [$name, $description]) {
                $this->output->write("- ");
                $this->output->write(Style::success()->apply(str_pad($name, $max)));
                $this->output->write(" | {$description}");
                $this->output->writeln(Ansi::RESET);
            }
            $this->output->writeln();
        }

        $this->output->writeln();
    }
}