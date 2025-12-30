<?php

namespace Tuto\Database\Seeders;

use Tuto\Console\Components\Output;

interface SeederInterface
{
    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return void
     */
    public function run(): void;

    /**
     * @param SeederRunner $runner
     * @return void
     */
    public function setRunner(SeederRunner $runner): void;

    /**
     * @param Output $output
     * @return void
     */
    public function setOutput(Output $output): void;
}