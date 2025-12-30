<?php

namespace Tuto\Console\Components\Output;

use Tuto\Console\Components\Ansi;

trait SimpleTextTrait
{
    /**
     * @param string $text
     * @return void
     */
    public function success(string $text): void
    {
        $this->simpleText(Ansi::FG_GREEN, $text);
    }

    /**
     * @param string $text
     * @return void
     */
    public function warning(string $text): void
    {
        $this->simpleText(Ansi::FG_YELLOW, $text);
    }

    /**
     * @param string $text
     * @return void
     */
    public function error(string $text): void
    {
        $this->simpleText(Ansi::FG_RED, $text);
    }

    /**
     * @param string $text
     * @return void
     */
    public function info(string $text): void
    {
        $this->simpleText(Ansi::FG_CYAN, $text);
    }

    /**
     * @param string $text
     * @return void
     */
    public function comment(string $text): void
    {
        $this->simpleText(Ansi::DIM, $text);
    }

    /**
     * @param string|Ansi $ansi
     * @param string $text
     * @return void
     */
    public function simpleText(string|Ansi $ansi, string $text): void
    {
        $ansi = is_string($ansi) ? $ansi : $ansi->value;
        echo "{$ansi}{$text}";
    }

    /**
     * @param string $text
     * @param string|Ansi $color
     * @return void
     */
    public function badge(string $text, string|Ansi $color = Ansi::FG_BLUE): void
    {
        $color = is_string($color) ? $color : $color->value;

        $this->write('[');
        $this->write("{$color}{$text}");
        $this->writeln(']');
    }
}