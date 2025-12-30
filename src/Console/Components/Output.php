<?php

namespace Tuto\Console\Components;

use Tuto\Console\Components\Input\QuestionComponent;
use Tuto\Console\Components\Output\BlockTextTrait;
use Tuto\Console\Components\Output\SimpleTextTrait;
use Tuto\Console\Terminal\Terminal;

class Output
{
    use SimpleTextTrait;
    use BlockTextTrait;

    /**
     * @param Terminal $terminal
     */
    public function __construct(public readonly Terminal $terminal)
    {
    }

    public function question(
        string $question,
        string|null $defaultValue = null,
        string $append = ': ',
    ): QuestionComponent {
        return new QuestionComponent($this, $question, $defaultValue, $append);
    }
}