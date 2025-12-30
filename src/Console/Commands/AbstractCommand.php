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
        return collect();
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
        // TODO: Default output for all commands
    }
}