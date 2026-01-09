<?php

namespace Tuto\Console\Components;

trait WriteOutputTrait
{
    /**
     * @param string $text
     * @param string $append
     * @return void
     */
    public function write(string $text = '', string $append = Ansi::RESET->value): void
    {
        echo $text;
        echo $append;
    }

    /**
     * @param string $text
     * @param string $append
     * @return void
     */
    public function writeln(string $text = '', string $append = Ansi::RESET->value): void
    {
        $this->write($text, "\n" . $append);
    }
}
