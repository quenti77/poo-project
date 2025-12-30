<?php

namespace Tuto\CLIOld\Input;

use Tuto\CLIOld\Output\Ansi;
use Tuto\CLIOld\Output\Output;

abstract class AbstractInput
{
    protected Output $output;
    protected string $question;

    public function __construct(Output $out, string $question)
    {
        $this->output = $out;
        $this->question = $question;
    }

    /**
     * Display the question
     */
    protected function displayQuestion(bool $bold = true): void
    {
        $style = Ansi::FG_CYAN;
        if ($bold) {
            $style .= Ansi::BOLD;
        }

        $this->output->writeln();
        $this->output->writeln($style . $this->question . Ansi::RESET);
    }

    /**
     * Format a prompt with optional default value
     */
    protected function formatPrompt(string $label = 'Votre choix', string|int|null $default = null): string
    {
        $defaultText = "";
        if ($default !== null) {
            $defaultText = Ansi::DIM . " [{$default}]" . Ansi::RESET;
        }

        return Ansi::FG_CYAN . $label . Ansi::RESET . "{$defaultText}: ";
    }

    /**
     * Format an option display with number and optional checkbox
     */
    protected function formatOption(
        int $number,
        string $value,
        string|int|null $key = null,
        bool $selected = false,
        bool $highlighted = false,
        bool $showCheckbox = false
    ): string {
        $numDisplay = Ansi::FG_YELLOW . "  [{$number}]" . Ansi::RESET;

        if ($highlighted) {
            $numDisplay = Ansi::FG_GREEN . Ansi::BOLD . "» [{$number}]" . Ansi::RESET;
        }

        $checkbox = "";
        if ($showCheckbox) {
            $checkbox = $selected
                ? Ansi::FG_GREEN . "☑" . Ansi::RESET
                : Ansi::DIM . "☐" . Ansi::RESET;
            $checkbox .= " ";
        }

        $keyDisplay = "";
        if (is_string($key)) {
            $keyDisplay = Ansi::DIM . " ({$key})" . Ansi::RESET;
        }

        $defaultMarker = $selected && !$showCheckbox
            ? Ansi::FG_GREEN . " ✓" . Ansi::RESET
            : "";

        $valueText = $value;
        if ($highlighted) {
            $valueText = Ansi::BOLD . $value . Ansi::RESET;
        }

        return "{$numDisplay} {$checkbox}{$valueText}{$keyDisplay}{$defaultMarker}";
    }

    /**
     * Abstract method that must be implemented by child classes
     */
    abstract public function ask(): mixed;
}
