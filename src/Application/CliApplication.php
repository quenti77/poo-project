<?php

namespace Tuto\Application;

use Tuto\Application\Loaders\ConfigurationLoader;
use Tuto\Application\Loaders\EnvironmentLoader;
use Tuto\Application\Loaders\ErrorHandlerLoader;
use Tuto\Application\Loaders\LoaderInterface;
use Tuto\Collections\Collection;
use Tuto\Console\Commands\AbstractCommand;
use Tuto\Console\Commands\CommandStatus;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;

class CliApplication extends BaseApplication
{
    /** @var Collection<string, AbstractCommand> $commands */
    private Collection $commands;

    public function __construct(
        private readonly Input $input,
        private readonly Output $output,
    ) {
        parent::__construct();

        $this->commands = collect();
    }

    /**
     * @return void
     */
    public function load(): void
    {
        foreach ($this->loaders() as $loader) {
            $loader->load();
        }
    }

    /**
     * @return void
     */
    public function boot(): void
    {
        $this->run();
    }

    public function addCommand(string $command): self
    {
        return $this->addCommandInstance(container($command));
    }

    public function addCommandInstance(AbstractCommand $command): self
    {
        $this->commands[$command->getName()] = $command;
        return $this;
    }

    /**
     * @return Collection<int, LoaderInterface>
     */
    protected function loaders(): Collection
    {
        return collect([
            new EnvironmentLoader(),
            new ConfigurationLoader(ROOT . '/config'),
            new ErrorHandlerLoader($this->output),
        ]);
    }

    /**
     * @return void
     */
    protected function run(): void
    {
        $commandName = $this->input->getCommandName();
        if ($commandName === null || $commandName === 'help') {
            $this->runHelp();
            exit(CommandStatus::SUCCESS->value);
        }

        $command = $this->commands->hasKeys($commandName) ? $this->commands[$commandName] : null;
        if ($command === null) {
            $this->output->error("Command not found '{$commandName}'");
            $this->runHelp();
            exit(CommandStatus::GENERIC_FAILURE->value);
        }

        if ($this->input->hasOption('h') || $this->input->hasOption('help')) {
            $command->runHelp($this->output);
            exit(CommandStatus::SUCCESS->value);
        }

        exit($command->execute($this->input, $this->output)->value);
    }

    /**
     * @return void
     */
    private function runHelp(): void
    {
        $this->output->blockSuccess("Available commands");

        $rows = $this->commands
            ->map(static fn ($key, AbstractCommand $c) => [$c->getName(), $c->getDescription()])
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
            $this->output->warning("{$groupName}:\n");
            foreach ($commands as [$name, $description]) {
                $this->output->write("- ");
                $this->output->success(str_pad($name, $max));
                $this->output->write(" | {$description}");
                $this->output->writeln();
            }
            $this->output->writeln();
        }

        $this->output->writeln();
    }
}