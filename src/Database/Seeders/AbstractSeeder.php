<?php

namespace Tuto\Database\Seeders;

use Throwable;
use Tuto\Console\Components\Output;
use Tuto\Database\ConnectionInterface;
use Tuto\Utils\CurrentTime;

abstract class AbstractSeeder implements SeederInterface
{
    protected SeederRunner $runner;
    protected Output $output;

    /**
     * @param ConnectionInterface $connection
     * @param CurrentTime $currentTime
     */
    public function __construct(
        protected readonly ConnectionInterface $connection,
        protected readonly CurrentTime $currentTime,
    ) {
    }

    /**
     * @param SeederRunner $runner
     * @return void
     */
    public function setRunner(SeederRunner $runner): void
    {
        $this->runner = $runner;
    }

    /**
     * @param Output $output
     * @return void
     */
    public function setOutput(Output $output): void
    {
        $this->output = $output;
    }

    /**
     * @param class-string<SeederInterface> $seederClass
     * @return void
     * @throws Throwable
     */
    protected function call(string $seederClass): void
    {
        $this->runner->runSeeder($seederClass);
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return collect(explode('\\', static::class))->reverse()->first();
    }
}
