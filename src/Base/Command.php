<?php

namespace Tuto\Base;

abstract class Command
{
    protected const int EXIT_SUCCESS = 0;
    protected const int EXIT_FAILURE = 1;

    public string $name = '';
    public string $description = '';

    abstract public function arguments(): array;

    abstract public function run(array $arguments): int;

}