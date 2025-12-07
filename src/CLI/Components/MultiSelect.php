<?php

namespace Tuto\CLI\Components;

use Throwable;
use Tuto\CLI\Ansi;
use Tuto\CLI\Output;
use Tuto\CLI\Terminal;

class MultiSelect extends AbstractInput
{
    /** @var array<int|string, string> */
    private array $options;
    /** @var array<int|string> */
    private array $defaults;
    private bool $required;
    private bool $useKeyboardNavigation;

    /**
     * @param array<int|string, string> $options
     * @param array<int|string> $defaults
     */
    public function __construct(
        Output $out,
        string $question,
        array $options,
        array $defaults = [],
        bool $required = false,
        bool $useKeyboardNavigation = true
    ) {
        parent::__construct($out, $question);
        $this->options = $options;
        $this->defaults = $defaults;
        $this->required = $required;
        $this->useKeyboardNavigation = $useKeyboardNavigation && Terminal::supportsInteractive();
    }

    /**
     * Ask and return selected keys
     * @return array<int|string>
     * @throws Throwable
     */
    public function ask(): array
    {
        if ($this->useKeyboardNavigation) {
            return $this->askWithKeyboard();
        }

        return $this->askWithInput();
    }

    /**
     * Ask using keyboard navigation (arrows + space to toggle)
     * @return array<int|string>
     * @throws Throwable
     */
    private function askWithKeyboard(): array
    {
        $keys = array_keys($this->options);
        $selected = $this->defaults;
        $highlightedIndex = 0;

        $this->displayQuestion();
        $this->out->comment("(Utilisez ↑↓ pour naviguer, Espace pour sélectionner, Entrée pour valider)");
        $this->out->writeln();

        Terminal::hideCursor();
        Terminal::enableRawMode();

        try {
            while (true) {
                $this->displayOptionsWithHighlight($highlightedIndex, $selected);

                $key = Terminal::readKey();

                if ($key === Terminal::KEY_UP) {
                    $highlightedIndex = ($highlightedIndex - 1 + count($keys)) % count($keys);
                    $this->clearOptions(count($keys));
                } elseif ($key === Terminal::KEY_DOWN) {
                    $highlightedIndex = ($highlightedIndex + 1) % count($keys);
                    $this->clearOptions(count($keys));
                } elseif ($key === Terminal::KEY_SPACE || $key === ' ') {
                    // Toggle selection
                    $currentKey = $keys[$highlightedIndex];
                    if (in_array($currentKey, $selected, true)) {
                        $selected = array_values(array_filter($selected, static fn($k) => $k !== $currentKey));
                    } else {
                        $selected[] = $currentKey;
                    }
                    $this->clearOptions(count($keys));
                } elseif ($key === Terminal::KEY_ENTER || $key === "\r") {
                    if (empty($selected) && $this->required) {
                        Terminal::disableRawMode();
                        Terminal::showCursor();
                        $this->clearOptions(count($keys));
                        $this->out->error("Vous devez sélectionner au moins une option.");
                        Terminal::hideCursor();
                        Terminal::enableRawMode();
                        $this->displayOptionsWithHighlight($highlightedIndex, $selected);
                        continue;
                    }
                    break;
                }
            }

            Terminal::disableRawMode();
            Terminal::showCursor();

            $this->out->writeln();
            $selectedValues = array_map(fn($k) => $this->options[$k], $selected);
            $this->out->success('Sélectionné: ' . implode(', ', $selectedValues));

            return $selected;
        } catch (Throwable $e) {
            Terminal::disableRawMode();
            Terminal::showCursor();
            throw $e;
        }
    }

    /**
     * Ask using text input (fallback)
     * @return array<int|string>
     */
    private function askWithInput(): array
    {
        $selected = $this->defaults;

        $this->displayQuestion();
        $this->out->writeln();
        $this->out->comment("Entrez les numéros séparés par des virgules (ex: 1,3,5)");
        $this->out->comment("Ou tapez 'all' pour tout sélectionner, 'none' pour tout désélectionner");
        $this->out->writeln();

        while (true) {
            $this->displayOptionsSimple($selected);

            $prompt = $this->formatPromptWithCount($selected);
            $this->out->write($prompt);

            $input = Terminal::readLine();

            if ($input === '') {
                if (!empty($selected) || !$this->required) {
                    return $selected;
                }
                $this->out->error("Vous devez sélectionner au moins une option.");
                continue;
            }

            if (strtolower($input) === 'all') {
                $selected = array_keys($this->options);
                $this->out->success("Toutes les options sélectionnées!");
                continue;
            }

            if (strtolower($input) === 'none') {
                $selected = [];
                $this->out->warning("Toutes les options désélectionnées.");
                continue;
            }

            if (strtolower($input) === 'done') {
                if (!empty($selected) || !$this->required) {
                    return $selected;
                }
                $this->out->error("Vous devez sélectionner au moins une option.");
                continue;
            }

            $numbers = array_map('trim', explode(',', $input));

            foreach ($numbers as $num) {
                $numericInput = filter_var($num, FILTER_VALIDATE_INT);
                if ($numericInput !== false) {
                    $keys = array_keys($this->options);
                    $index = $numericInput - 1;
                    if (isset($keys[$index])) {
                        $key = $keys[$index];
                        if (in_array($key, $selected)) {
                            $selected = array_values(array_filter($selected, fn($k) => $k !== $key));
                            $this->out->warning("Désélectionné: {$this->options[$key]}");
                        } else {
                            $selected[] = $key;
                            $this->out->success("Sélectionné: {$this->options[$key]}");
                        }
                    }
                } elseif (array_key_exists($num, $this->options)) {
                    if (in_array($num, $selected)) {
                        $selected = array_values(array_filter($selected, fn($k) => $k !== $num));
                        $this->out->warning("Désélectionné: {$this->options[$num]}");
                    } else {
                        $selected[] = $num;
                        $this->out->success("Sélectionné: {$this->options[$num]}");
                    }
                }
            }

            $this->out->writeln();
        }
    }

    /**
     * Ask and return selected values
     * @return array<string>
     */
    public function askValues(): array
    {
        $keys = $this->ask();
        return array_map(fn($key) => $this->options[$key], $keys);
    }

    /**
     * Display options with highlighting and checkboxes
     * @param array<int|string> $selected
     */
    private function displayOptionsWithHighlight(int $highlightedIndex, array $selected): void
    {
        $index = 0;
        foreach ($this->options as $key => $value) {
            $number = $index + 1;
            $highlighted = ($index === $highlightedIndex);
            $isSelected = in_array($key, $selected);

            $line = $this->formatOption($number, $value, $key, $isSelected, $highlighted, true);
            $this->out->writeln($line);
            $index++;
        }
    }

    /**
     * Display options without highlighting
     * @param array<int|string> $selected
     */
    private function displayOptionsSimple(array $selected): void
    {
        $index = 0;
        foreach ($this->options as $key => $value) {
            $number = $index + 1;
            $isSelected = in_array($key, $selected);

            $line = $this->formatOption($number, $value, $key, $isSelected, false, true);
            $this->out->writeln($line);
            $index++;
        }
        $this->out->writeln();
    }

    /**
     * Clear N lines (for redrawing options)
     */
    private function clearOptions(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            Terminal::cursorUp();
            Terminal::clearLine();
        }
    }

    /**
     * Format prompt with selection count
     * @param array<int|string> $selected
     */
    private function formatPromptWithCount(array $selected): string
    {
        $count = count($selected);
        $countText = $count > 0
            ? Ansi::FG_GREEN . " ({$count} sélectionné" . ($count > 1 ? 's' : '') . ")" . Ansi::RESET
            : "";

        return Ansi::FG_CYAN . "Sélection" . Ansi::RESET . "{$countText} " .
               Ansi::DIM . "(tapez 'done' pour terminer)" . Ansi::RESET . ": ";
    }
}
