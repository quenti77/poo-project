<?php

namespace Tuto\CLI\Components;

use Tuto\CLI\Ansi;
use Tuto\CLI\Output;

class ProgressBar
{
    private int $max;
    private int $current = 0;
    private Output $out;
    private int $barWidth = 30;

    public function __construct(Output $out, int $max)
    {
        $this->out = $out;
        $this->max = max(1, $max);
    }

    public function advance(int $step = 1): void
    {
        $this->current += $step;
        if ($this->current > $this->max) {
            $this->current = $this->max;
        }

        $this->display();
    }

    public function finish(): void
    {
        $this->current = $this->max;
        $this->display();
        $this->out->writeln();
    }

    private function display(): void
    {
        $percent = $this->current / $this->max;
        $filled  = (int) round($percent * $this->barWidth);
        $empty   = $this->barWidth - $filled;

        $bar =
            Ansi::fgRgb(0, 200, 100) .
            str_repeat("█", $filled) .
            Ansi::RESET .
            str_repeat("░", $empty);

        $percentText = sprintf("%3d%%", (int) round($percent * 100));

        echo "\r{$bar} {$percentText}";
        flush();
    }
}
