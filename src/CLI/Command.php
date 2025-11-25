<?php

namespace Tuto\CLI;

abstract class Command
{
    public const int EXIT_SUCCESS = 0;
    public const int EXIT_FAILURE = 1;

    abstract public function getName(): string;

    public function getDescription(): string
    {
        return '';
    }

    public function configure(): void
    {
    }

    abstract public function execute(Input $input, Output $output): int;
}