<?php

namespace Tuto\Console\Components\Output\Table;

use Closure;
use Tuto\Collections\Collection;
use Tuto\Console\Components\AbstractComponent;
use Tuto\Console\Components\Ansi;
use Tuto\Console\Components\Output;
use Tuto\Console\Components\Output\Borders\BorderInterface;
use Tuto\Console\Components\Output\Borders\OutputBorder;

class TableComponent extends AbstractComponent
{
    public function __construct(
        Output $output,
        /** @param Collection<int, Column> $columns */
        protected readonly Collection $columns,
        /** @param Collection<int, Collection<int|string, mixed>|array<int|string, mixed>> $items */
        protected readonly Collection $items,
        protected OutputBorder $border = OutputBorder::NONE,
        protected int $cellPaddingLeft = 1,
        protected int $cellPaddingRight = 1,
        protected TruncateStrategy $truncateStrategy = TruncateStrategy::NO_LIMIT,
        protected Closure|null $rowColorizer = null,
        protected bool $showRowNumbers = false,
    ) {
        parent::__construct($output);
    }

    /**
     * @param OutputBorder $border
     * @return $this
     */
    public function withBorder(OutputBorder $border): static
    {
        $this->border = $border;
        return $this;
    }

    /**
     * @param TruncateStrategy $truncateStrategy
     * @return $this
     */
    public function withTruncateStrategy(TruncateStrategy $truncateStrategy): static
    {
        $this->truncateStrategy = $truncateStrategy;
        return $this;
    }

    /**
     * @param int $left
     * @param int $right
     * @return $this
     */
    public function withPadding(int $left, int $right): static
    {
        $this->cellPaddingLeft = $left;
        $this->cellPaddingRight = $right;
        return $this;
    }

    /**
     * @param callable $callback
     * @return $this
     */
    public function withRowColorizer(callable $callback): static
    {
        $this->rowColorizer = $callback;
        return $this;
    }

    /**
     * @return $this
     */
    public function withRowNumbers(): static
    {
        $this->showRowNumbers = true;
        return $this;
    }

    /**
     * @param Column $column
     * @return $this
     */
    public function addColumn(Column $column): static
    {
        $this->columns->push($column);
        return $this;
    }

    /**
     * @return bool
     */
    public function run(): bool
    {
        if ($this->columns->isEmpty()) {
            return true;
        }

        $columnWidths = $this->calculateColumnWidth();

        $this->renderTopBorder($columnWidths);
        $this->renderHeaderRow($columnWidths);
        $this->renderMiddleBorder($columnWidths);

        if ($this->items->isEmpty()) {
            $borderChar = $this->border->getBorder();

            $size = array_sum($columnWidths->values()->all());
            $size -= $this->cellPaddingLeft + $this->cellPaddingRight + 2;

            $content = $this->renderCell("No data found", $size, Ansi::FG_YELLOW, Alignment::CENTER);
            $line = $borderChar->vertical() . $content . $borderChar->vertical();
            $this->output->writeln($line);
        } else {
            foreach ($this->items as $index => $item) {
                $this->renderDataRow($item, $columnWidths, $index);
            }
        }

        $this->renderBottomBorder($columnWidths);
        return true;
    }

    /**
     * @return Collection<int, int>
     */
    private function calculateColumnWidth(): Collection
    {
        $widths = collect();

        foreach ($this->columns as $index => $column) {
            $widths[$index] = mb_strlen($column->name);
        }

        if ($this->showRowNumbers) {
            $maxRowNumber = $this->items->count();
            $widths->unshift(mb_strlen((string) $maxRowNumber));
        }

        $startIndex = $this->showRowNumbers ? 1 : 0;
        foreach ($this->items as $item) {
            $row = $this->mapItemToRow($item);
            foreach ($row as $index => $value) {
                $currentIndex = $startIndex + $index;
                $cellWidth = mb_strlen((string) $value);
                $widths[$currentIndex] = max($widths[$currentIndex] ?? 0, $cellWidth);
            }
        }

        foreach ($this->columns as $index => $column) {
            $currentIndex = $startIndex + $index;
            $widths[$currentIndex] = min($widths[$currentIndex] ?? 0, $column->maxSize);
        }

        if ($this->truncateStrategy !== TruncateStrategy::NO_LIMIT) {
            $widths = $this->adjustWidthsToTerminal($widths);
        }
        return $widths;
    }

    /**
     * @param Collection $widths
     * @return Collection
     */
    private function adjustWidthsToTerminal(Collection $widths): Collection
    {
        $terminalWidth = $this->output->terminal->getTerminalSize()->width;

        $borderChar = $this->border->getBorder();
        $columnsCount = $widths->count();

        $bordersWidth = ($columnsCount + 1) * mb_strlen($borderChar->vertical());
        $paddingWidth = $columnsCount * ($this->cellPaddingLeft + $this->cellPaddingRight);
        $totalColumnsWidth = array_sum($widths->values()->all());
        $totalTableWidth = $bordersWidth + $paddingWidth + $totalColumnsWidth;

        if ($totalTableWidth <= $terminalWidth) {
            return $widths;
        }

        $availableWidth = $terminalWidth - $bordersWidth - $paddingWidth;
        if ($this->truncateStrategy !== TruncateStrategy::TRUNCATE) {
            return $widths;
        }

        $ratio = $availableWidth / $totalColumnsWidth;
        foreach ($widths as $index => $width) {
            $newWidth = (int) floor($width * $ratio);
            $widths[$index] = max(3, $newWidth);
        }

        return $widths;
    }

    /**
     * @param Collection<int|string, mixed>|array<int|string, mixed> $item
     * @return array
     */
    private function mapItemToRow(Collection|array $item): array
    {
        return is_array($item) ? array_values($item) : $item->values()->all();
    }

    /**
     * @param string $text
     * @param int $width
     * @param Alignment $alignment
     * @return string
     */
    private function alignText(string $text, int $width, Alignment $alignment): string
    {
        $textLength = mb_strlen($text);
        if ($textLength >= $width) {
            return $text;
        }

        $padding = $width - $textLength;
        $middlePadding = (int) floor($padding / 2);
        return match($alignment) {
            Alignment::LEFT => $text . str_repeat(' ', $padding),
            Alignment::CENTER => str_repeat(' ', $middlePadding) . $text . str_repeat(' ', $middlePadding),
            Alignment::RIGHT => str_repeat(' ', $padding) . $text,
        };
    }

    /**
     * @param string $text
     * @param int $maxWidth
     * @return string
     */
    private function truncateText(string $text, int $maxWidth): string
    {
        if (mb_strlen($text) <= $maxWidth) {
            return $text;
        }

        // TRUNCATE AND WRAP Strategy ar identical at this moment
        return $maxWidth < 3
            ? mb_substr($text, 0, $maxWidth)
            : mb_substr($text, 0, $maxWidth - 3) . '...';
    }

    /**
     * @param Collection<int, int> $columnWidths
     * @return void
     */
    private function renderTopBorder(Collection $columnWidths): void
    {
        $borderChar = $this->border->getBorder();
        $parts = $this->getPartsLineBorder($columnWidths, $borderChar);

        $line = $borderChar->topLeft() . $parts->join($borderChar->topT()) . $borderChar->topRight();
        $this->output->writeln($line);
    }

    /**
     * @param Collection<int, int> $columnWidths
     * @return void
     */
    private function renderMiddleBorder(Collection $columnWidths): void
    {
        $borderChar = $this->border->getBorder();
        $parts = $this->getPartsLineBorder($columnWidths, $borderChar);

        $line = $borderChar->leftT() . $parts->join($borderChar->cross()) . $borderChar->rightT();
        $this->output->writeln($line);
    }

    /**
     * @param Collection<int, int> $columnWidths
     * @return void
     */
    private function renderBottomBorder(Collection $columnWidths): void
    {
        $borderChar = $this->border->getBorder();
        $parts = $this->getPartsLineBorder($columnWidths, $borderChar);

        $line = $borderChar->bottomLeft() . $parts->join($borderChar->bottomT()) . $borderChar->bottomRight();
        $this->output->writeln($line);
    }

    /**
     * @param Collection<int, int> $columnWidths
     * @return Collection<int, string>
     */
    private function getPartsLineBorder(Collection $columnWidths, BorderInterface $borderChar): Collection
    {
        $parts = collect();

        foreach ($columnWidths as $width) {
            $totalWidth = $width + $this->cellPaddingLeft + $this->cellPaddingRight;
            $parts->push(str_repeat($borderChar->horizontal(), $totalWidth));
        }

        return $parts;
    }

    /**
     * @param string $text
     * @param Ansi|null $ansi
     * @return string
     */
    private function applyAnsiToCell(string $text, Ansi|null $ansi): string
    {
        return $ansi === null ? $text : $ansi->value . $text . Ansi::RESET->value;
    }

    /**
     * @param Collection<int, int> $columnWidths
     * @return void
     */
    private function renderHeaderRow(Collection $columnWidths): void
    {
        $borderChar = $this->border->getBorder();
        $cells = collect();

        $workingWidths = clone $columnWidths;
        if ($this->showRowNumbers) {
            $cells->push($this->renderCell('#', $workingWidths[0], Ansi::BOLD, Alignment::RIGHT));
            $workingWidths->shift();
        }

        foreach ($this->columns as $index => $column) {
            $renderCell = $this->renderCell(
                $column->name,
                $workingWidths[$index],
                $column->headerStyle,
                $column->alignment,
            );
            $cells->push($renderCell);
        }

        $line = $borderChar->vertical() . $cells->join($borderChar->vertical()) . $borderChar->vertical();
        $this->output->writeln($line);
    }

    /**
     * @param Collection<int|string, mixed>|array<int|string, mixed> $item
     * @param Collection $columnWidths
     * @param int $rowIndex
     * @return void
     */
    private function renderDataRow(Collection|array $item, Collection $columnWidths, int $rowIndex): void
    {
        $borderChar = $this->border->getBorder();
        $row = $this->mapItemToRow($item);
        $cells = collect();

        $workingWidths = clone ($columnWidths);

        $rowStyle = null;
        if ($this->rowColorizer !== null) {
            $rowStyle = ($this->rowColorizer)($item, $rowIndex);
        }

        if ($this->showRowNumbers) {
            $renderCell = $this->renderCell(
                (string) ($rowIndex + 1),
                $workingWidths[0],
                $rowStyle ?? Ansi::DIM,
                Alignment::RIGHT,
            );
            $cells->push($renderCell);
            $workingWidths->shift();
        }

        foreach ($row as $index => $value) {
            $column = $this->columns[$index];
            $cellStyle = $rowStyle ?? $column->rowStyle ?? null;

            $renderCell = $this->renderCell(
                (string) $value,
                $workingWidths[$index],
                $cellStyle,
                $column->alignment,
            );
            $cells->push($renderCell);
        }

        $line = $borderChar->vertical() . $cells->join($borderChar->vertical()) . $borderChar->vertical();
        $this->output->writeln($line);
    }

    /**
     * @param string $text
     * @param int $width
     * @param Ansi|null $style
     * @param Alignment $alignment
     * @return string
     */
    private function renderCell(string $text, int $width, Ansi|null $style, Alignment $alignment): string {
        if (mb_strlen($text) > $width) {
            $text = $this->truncateText($text, $width);
        }
        $text = $this->alignText($text, $width, $alignment);

        $paddingLeft = str_repeat(' ', $this->cellPaddingLeft);
        $paddingRight = str_repeat(' ', $this->cellPaddingRight);
        $cell = $paddingLeft . $text . $paddingRight;

        if ($style !== null) {
            $cell = $this->applyAnsiToCell($cell, $style);
        }
        return $cell;
    }
}
