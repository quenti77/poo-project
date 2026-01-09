<?php

namespace Tuto\Console\Components;

abstract class AbstractComponent
{
    /**
     * @param Output $output
     */
    public function __construct(
        protected readonly Output $output,
    ) {
    }

    /**
     * @return mixed
     */
    abstract public function run(): mixed;
}
