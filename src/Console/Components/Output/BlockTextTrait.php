<?php

namespace Tuto\Console\Components\Output;

use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\WriteOutputTrait;

trait BlockTextTrait
{
    use WriteOutputTrait;

    /**
     * @param string $text
     * @return void
     */
    public function blockSuccess(string $text): void
    {
        $this->block($text, Ansi::BG_GREEN);
    }

    /**
     * @param string $text
     * @return void
     */
    public function blockWarning(string $text): void
    {
        $this->block($text, Ansi::BG_YELLOW, Ansi::FG_BLACK);
    }

    /**
     * @param string $text
     * @return void
     */
    public function blockError(string $text): void
    {
        $this->block($text, Ansi::BG_RED);
    }

    /**
     * @param string $text
     * @return void
     */
    public function blockInfo(string $text): void
    {
        $this->block($text, Ansi::BG_CYAN);
    }

    /**
     * @param string $text
     * @param string|Ansi $bgColor
     * @param string|Ansi $fgColor
     * @param int $padding
     * @param bool $verticalPadding
     * @return void
     */
    public function block(
        string $text,
        string|Ansi $bgColor = Ansi::BG_BLUE,
        string|Ansi $fgColor = Ansi::FG_WHITE,
        int $padding = 2,
        bool $verticalPadding = true
    ): void {
        $fgColor = is_string($fgColor) ? $fgColor : $fgColor->value;
        $bgColor = is_string($bgColor) ? $bgColor : $bgColor->value;

        $lines = explode("\n", $text);
        $maxLength = max(array_map('mb_strlen', $lines));
        $style = $fgColor . $bgColor;

        $totalWidth = $maxLength + ($padding * 2);
        $emptyLine = str_repeat(' ', $totalWidth);

        if ($verticalPadding) {
            $this->writeln($style . $emptyLine);
        }

        foreach ($lines as $line) {
            $lineLength = mb_strlen($line);
            $rightPadding = str_repeat(' ', $maxLength - $lineLength);
            $paddedLine = str_repeat(' ', $padding) . $line . $rightPadding . str_repeat(' ', $padding);
            $this->writeln($style . $paddedLine);
        }

        if ($verticalPadding) {
            $this->writeln($style . $emptyLine);
        }
        $this->writeln();
    }
}
