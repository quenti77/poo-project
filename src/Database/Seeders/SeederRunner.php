<?php

namespace Tuto\Database\Seeders;

use InvalidArgumentException;
use Throwable;
use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\Output;

class SeederRunner
{
    private const int DEPTH_SIZE = 2;

    /** @var int $depth */
    private int $depth = 0;

    /**
     * @param Output $output
     */
    public function __construct(private readonly Output $output)
    {
    }

    /**
     * @param class-string<SeederInterface> $seederClass
     * @return void
     * @throws Throwable
     */
    public function runSeeder(string $seederClass): void
    {
        $seeder = container($seederClass);
        if (!($seeder instanceof SeederInterface)) {
            $message = sprintf("'%s' must be an instance of '%s'", $seederClass, SeederInterface::class);
            throw new InvalidArgumentException($message);
        }

        $seeder->setRunner($this);
        $seeder->setOutput($this->output);

        $this->outputStatus($seeder->getName(), 'process', Ansi::FG_YELLOW);
        try {
            $this->depth += static::DEPTH_SIZE;
            $seeder->run();
            $this->depth -= static::DEPTH_SIZE;
            $this->outputStatus($seeder->getName(), 'done', Ansi::FG_GREEN);
            $this->output->writeln();
        } catch (Throwable $exception) {
            $this->depth -= static::DEPTH_SIZE;
            $this->outputStatus($seeder->getName(), 'error', Ansi::FG_RED);
            throw $exception;
        }
    }

    /**
     * @param string $seederName
     * @param string $status
     * @param Ansi $badgeColor
     * @return void
     */
    private function outputStatus(string $seederName, string $status, Ansi $badgeColor): void
    {
        $this->output->write(str_repeat(' ', $this->depth));
        $this->output->write('- ');
        $this->output->write($seederName . ' ');
        $this->output->badge($status, $badgeColor);
    }
}