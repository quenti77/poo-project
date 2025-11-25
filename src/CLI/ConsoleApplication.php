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
        $this->commands = new Collection();

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
            $this->output->writeln(Ansi::BG_RED . Ansi::FG_WHITE . "Command not found '{$commandName}'" . Ansi::RESET);
            $this->runHelp();
            return Command::EXIT_FAILURE;
        }

        try {
            return $command->execute($input, $this->output);
        } catch (Throwable $exception) {
            $this->output->writeln(Ansi::BG_RED . Ansi::FG_WHITE . $exception->getMessage() . Ansi::RESET);
            return Command::EXIT_FAILURE;
        }
    }

    private function runHelp(): void
    {
        $this->output->title(Ansi::FG_GREEN . "Available commands");

        $rows = $this->commands
            ->map(static fn (Command $c) => [$c->getName(), $c->getDescription()])
            ->all();
        $rows[] = ['help', 'Get the list of available commands'];
        usort($rows, static fn (array $a, array $b) => strcmp($a[0], $b[0]));

        $max = 0;
        $groups = [];
        foreach ($rows as $row) {
            $max = max($max, strlen($row[0]));

            $parts = explode(':', $row[0]);
            $commandName = array_pop($parts);
            $groupName = implode(':', $parts + ['__global']);

            $groups[$groupName][] = $row;
        }

        foreach ($groups as $groupName => $commands) {
            $this->output->styled("{$groupName}:\n", Style::create()->fgStandard(Ansi::FG_YELLOW));
            foreach ($commands as [$name, $description]) {
                $this->output->write("- ");
                $this->output->write(Style::create()->fgStandard(Ansi::FG_GREEN)->apply($name));
                $this->output->write(" | {$description}");
            }
            $this->output->writeln("\n\n");
        }

        $this->output->writeln();
    }
}