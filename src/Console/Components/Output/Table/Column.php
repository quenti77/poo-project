<?php

namespace Tuto\Console\Components\Output\Table;

use Tuto\Console\Components\Ansi;

class Column
{
    public function __construct(
        public readonly string $name,
        public readonly Alignment $alignment,
        public readonly Ansi $headerStyle,
        public readonly Ansi $rowStyle,
        public readonly int $maxSize = 50,
    ) {
    }

    /**
     * @param string $name
     * @return self
     */
    public static function make(string $name): self
    {
        return new self(
            $name,
            Alignment::LEFT,
            Ansi::BOLD,
            Ansi::RESET,
        );
    }
}
