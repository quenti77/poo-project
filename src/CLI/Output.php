<?php

namespace Tuto\CLI;

class Output
{
    public function write(string $text): void
    {
        echo $text;
    }

    public function writeln(string $text = ""): void
    {
        echo $text . PHP_EOL;
    }

    public function styled(string $text, Style $style): void
    {
        $this->writeln($style->apply($text));
    }

    public function title(string $text): void
    {
        $this->writeln();
        $this->writeln("=== " . Style::create()->apply($text) . " ===");
        $this->writeln();
    }

    public function block(string $text, string $color = Ansi::FG_BLUE): void
    {
        $styled = $color . $text . Ansi::RESET;
        $this->writeln("[{$styled}]");
    }

    public function table(array $rows): void
    {
        if (empty($rows)) return;

        $cols = [];
        foreach ($rows as $row) {
            foreach ($row as $i => $cell) {
                $cols[$i] = max($cols[$i] ?? 0, strlen($cell));
            }
        }

        foreach ($rows as $row) {
            $line = "";
            foreach ($row as $i => $cell) {
                $line .= str_pad($cell, $cols[$i]) . "   ";
            }
            $this->writeln($line);
        }
    }

    public function progress(int $max): ProgressBar
    {
        return new ProgressBar($this, $max);
    }
}
