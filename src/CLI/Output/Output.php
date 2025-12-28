<?php

namespace Tuto\CLI\Output;

use Tuto\CLI\Terminal;

class Output
{
    /**
     * @param Terminal $terminal
     */
    public function __construct(public readonly Terminal $terminal)
    {
        $this->terminal->init();
    }

    /**
     * @param string $text
     * @return void
     */
    public function write(string $text): void
    {
        echo $text;
    }

    /**
     * @param string $text
     * @return void
     */
    public function writeln(string $text = ""): void
    {
        echo $text . PHP_EOL;
    }

    public function success(string $text): void
    {
        $this->writeln(Ansi::FG_GREEN . $text . Ansi::RESET);
    }

    public function error(string $text): void
    {
        $this->writeln(Ansi::FG_RED . $text . Ansi::RESET);
    }

    public function warning(string $text): void
    {
        $this->writeln(Ansi::FG_YELLOW . $text . Ansi::RESET);
    }

    public function info(string $text): void
    {
        $this->writeln(Ansi::FG_CYAN . $text . Ansi::RESET);
    }

    public function comment(string $text): void
    {
        $this->writeln(Ansi::DIM . $text . Ansi::RESET);
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

    /**
     * @param string $text
     * @param string $color
     * @return void
     */
    public function badge(string $text, string $color = Ansi::FG_BLUE): void
    {
        $styled = $color . $text . Ansi::RESET;
        $this->writeln("[{$styled}]");
    }

    /**
     * @param string $text
     * @param string $fgColor
     * @param string $bgColor
     * @param int $padding
     * @param bool $verticalPadding
     * @return void
     */
    public function block(
        string $text,
        string $fgColor = Ansi::FG_WHITE,
        string $bgColor = Ansi::BG_BLUE,
        int $padding = 2,
        bool $verticalPadding = true
    ): void {
        $lines = explode("\n", $text);
        // Use mb_strlen to count visual characters, not bytes (UTF-8 support)
        $maxLength = max(array_map('mb_strlen', $lines));
        $style = $fgColor . $bgColor;

        // Calculate total width (text + padding on both sides)
        $totalWidth = $maxLength + ($padding * 2);
        $emptyLine = str_repeat(' ', $totalWidth);

        if ($verticalPadding) {
            $this->writeln($style . $emptyLine . Ansi::RESET);
        }

        foreach ($lines as $line) {
            // Use mb_str_pad for UTF-8 support (or manual padding)
            $lineLength = mb_strlen($line);
            $rightPadding = str_repeat(' ', $maxLength - $lineLength);
            $paddedLine = str_repeat(' ', $padding) . $line . $rightPadding . str_repeat(' ', $padding);
            $this->writeln($style . $paddedLine . Ansi::RESET);
        }

        if ($verticalPadding) {
            $this->writeln($style . $emptyLine . Ansi::RESET);
        }
        $this->writeln();
    }

    /**
     * @param array $rows
     * @return void
     */
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
}