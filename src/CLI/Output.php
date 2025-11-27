<?php

namespace Tuto\CLI;

use Tuto\CLI\Components\ProgressBar;

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

    public function badge(string $text, string $color = Ansi::FG_BLUE): void
    {
        $styled = $color . $text . Ansi::RESET;
        $this->writeln("[{$styled}]");
    }

    public function block(
        string $text,
        string $fgColor = Ansi::FG_WHITE,
        string $bgColor = Ansi::BG_BLUE,
        int $padding = 2,
        bool $verticalPadding = true
    ): void {
        $lines = explode("\n", $text);
        $maxLength = max(array_map('strlen', $lines));
        $paddedWidth = $maxLength + ($padding * 2);

        $emptyLine = str_repeat(' ', $paddedWidth);
        $style = $fgColor . $bgColor;

        if ($verticalPadding) {
            $this->writeln($style . $emptyLine . Ansi::RESET);
        }

        foreach ($lines as $line) {
            $paddedLine = str_repeat(' ', $padding) . str_pad($line, $maxLength) . str_repeat(' ', $padding);
            $this->writeln($style . $paddedLine . Ansi::RESET);
        }

        if ($verticalPadding) {
            $this->writeln($style . $emptyLine . Ansi::RESET);
        }
        $this->writeln();
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

    public function success(string $text): void
    {
        $this->styled($text, Style::create()->standard(Ansi::FG_GREEN));
    }

    public function error(string $text): void
    {
        $this->styled($text, Style::create()->standard(Ansi::FG_RED)->bold());
    }

    public function warning(string $text): void
    {
        $this->styled($text, Style::create()->standard(Ansi::FG_YELLOW));
    }

    public function info(string $text): void
    {
        $this->styled($text, Style::create()->standard(Ansi::FG_CYAN));
    }

    public function comment(string $text): void
    {
        $this->styled($text, Style::create()->dim());
    }

    public function successBlock(string $text, int $padding = 2): void
    {
        $this->block($text, Ansi::FG_WHITE, Ansi::BG_GREEN, $padding);
    }

    public function errorBlock(string $text, int $padding = 2): void
    {
        $this->block($text, Ansi::FG_WHITE, Ansi::BG_RED, $padding);
    }

    public function warningBlock(string $text, int $padding = 2): void
    {
        $this->block($text, Ansi::FG_BLACK, Ansi::BG_YELLOW, $padding);
    }

    public function infoBlock(string $text, int $padding = 2): void
    {
        $this->block($text, Ansi::FG_WHITE, Ansi::BG_CYAN, $padding);
    }
}
