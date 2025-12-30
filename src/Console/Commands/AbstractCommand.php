<?php

namespace Tuto\Console\Commands;

use Tuto\Collections\Collection;
use Tuto\Console\Components\Input;
use Tuto\Console\Components\Output;

abstract class AbstractCommand
{
    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    abstract public function getDescription(): string;

    /**
     * @param Input $input
     * @param Output $output
     * @return CommandStatus
     */
    abstract public function execute(Input $input, Output $output): CommandStatus;

    /**
     * @return Collection<string, string|null>
     */
    public function getArguments(): Collection
    {
        return collect();
    }

    /**
     * @return Collection<string, string|bool>
     */
    public function getOptions(): Collection
    {
        return collect([
            'h' => true,
            'help' => true,
        ]);
    }

    /**
     * @return Collection<int, string>
     */
    public function getExamples(): Collection
    {
        return collect();
    }

    /**
     * @param Output $output
     * @return void
     */
    final public function runHelp(Output $output): void
    {
        $output->blockSuccess("Command: {$this->getName()}");
        $output->writeln($this->getDescription());
        $output->writeln();

        // Arguments
        $arguments = $this->getArguments();
        if (!$arguments->isEmpty()) {
            $output->warning("Arguments:\n");
            $maxLength = max(0, ...$arguments->keys()->map(fn($key) => strlen($key))->all());

            foreach ($arguments as $name => $defaultValue) {
                $output->write("  ");
                $output->success(str_pad($name, $maxLength));
                if ($defaultValue === null) {
                    $output->write("  (required)");
                } else {
                    $output->write("  (default: {$defaultValue})");
                }
                $output->writeln();
            }
            $output->writeln();
        }

        // Options
        $options = $this->getOptions();
        if (!$options->isEmpty()) {
            $output->warning("Options:\n");
            $maxLength = max(0, ...$options->keys()->map(fn($key) => strlen($key))->all());

            foreach ($options as $name => $value) {
                $output->write("  ");
                $prefix = strlen($name) === 1 ? '-' : '--';
                $formattedName = $prefix . $name;
                $output->success(str_pad($formattedName, $maxLength + 2));

                if (is_string($value)) {
                    $output->write("  {$value}");
                }
                $output->writeln();
            }
            $output->writeln();
        }

        // Examples
        $examples = $this->getExamples();
        if (!$examples->isEmpty()) {
            $output->warning("Examples:");
            $output->writeln();
            foreach ($examples as $example) {
                $output->write("  {$example}");
                $output->writeln();
            }
            $output->writeln();
        }
    }
}