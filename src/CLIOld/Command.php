<?php

namespace Tuto\CLIOld;

use Tuto\CLIOld\Input\Input;
use Tuto\CLIOld\Output\Output;

abstract class Command
{
    public const int EXIT_SUCCESS = 0;
    public const int EXIT_FAILURE = 1;

    abstract public function execute(Input $input, Output $output): int;

    /**
     * @return string
     */
    abstract public function getName(): string;

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return '';
    }
}