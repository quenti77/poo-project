<?php

namespace Tuto\Console\Components;

use Tuto\Collections\Collection;
use Tuto\Console\Components\Input\QuestionComponent;
use Tuto\Console\Components\Output\BlockTextTrait;
use Tuto\Console\Components\Output\SimpleTextTrait;
use Tuto\Console\Components\Output\Table\TableComponent;
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

    /**
     * @param Collection $items
     * @param Collection|null $columns
     * @return TableComponent
     */
    public function table(Collection $items, Collection|null $columns = null): TableComponent
    {
        $columns ??= collect();
        return new TableComponent($this, $columns, $items);
    }
}