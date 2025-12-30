<?php

namespace Tuto\CLIOld\Input;

use Tuto\CLIOld\Output\Ansi;
use Tuto\CLIOld\Output\Cursor;
use Tuto\CLIOld\Output\Output;

class Suggest extends AbstractInput
{
    /** @var array<string> */
    private array $options;
    private string|null $default;
    private int $maxSuggestions;
    private bool $useKeyboardNavigation;

    /**
     * @param array<string> $options
     */
    public function __construct(
        Output $out,
        string $question,
        array $options,
        string|null $default = null,
        int $maxSuggestions = 5,
        bool $useKeyboardNavigation = true
    ) {
        parent::__construct($out, $question);
        $this->options = array_values($options);
        $this->default = $default;
        $this->maxSuggestions = $maxSuggestions;
        $this->useKeyboardNavigation = $useKeyboardNavigation && $this->output->terminal->supportsInteractive();
    }

    public function ask(): string
    {
        if ($this->useKeyboardNavigation) {
            return $this->askWithAutocomplete();
        }

        return $this->askWithInput();
    }

    /**
     * Ask with interactive autocomplete
     */
    private function askWithAutocomplete(): string
    {
        $input = '';
        $cursorPos = 0;
        $selectedSuggestionIndex = 0;
        $lastSuggestionCount = 0;

        $this->displayQuestion(false);
        $this->output->comment("(Tapez pour voir les suggestions, ↑↓ pour naviguer, Tab/Entrée pour sélectionner)");
        $this->output->writeln();

        $prompt = $this->formatPrompt('', $this->default);
        $this->output->write($prompt);
        $promptLength = mb_strlen(strip_tags($prompt));

        $this->output->terminal->enableRawMode();

        try {
            while (true) {
                $key = $this->output->terminal->readKey();

                if ($key === Cursor::KEY_ENTER || $key === "\r") {
                    $suggestions = $this->findSuggestions($input);
                    if (!empty($suggestions) && $selectedSuggestionIndex < count($suggestions)) {
                        $input = $suggestions[$selectedSuggestionIndex];
                    }

                    if ($input === '' && $this->default !== null) {
                        $input = $this->default;
                    }

                    if ($input !== '') {
                        break;
                    }
                } elseif ($key === Cursor::KEY_TAB || $key === "\t") {
                    $suggestions = $this->findSuggestions($input);
                    if (!empty($suggestions)) {
                        $input = $suggestions[$selectedSuggestionIndex];
                        $cursorPos = mb_strlen($input);
                        $selectedSuggestionIndex = 0;
                        $lastSuggestionCount = $this->redrawAll($input, $cursorPos, $promptLength, $lastSuggestionCount, $selectedSuggestionIndex);
                    }
                } elseif ($key === Cursor::KEY_UP) {
                    $suggestions = $this->findSuggestions($input);
                    if (!empty($suggestions)) {
                        $selectedSuggestionIndex = ($selectedSuggestionIndex - 1 + count($suggestions)) % count($suggestions);
                        $lastSuggestionCount = $this->redrawAll($input, $cursorPos, $promptLength, $lastSuggestionCount, $selectedSuggestionIndex);
                    }
                } elseif ($key === Cursor::KEY_DOWN) {
                    $suggestions = $this->findSuggestions($input);
                    if (!empty($suggestions)) {
                        $selectedSuggestionIndex = ($selectedSuggestionIndex + 1) % count($suggestions);
                        $lastSuggestionCount = $this->redrawAll($input, $cursorPos, $promptLength, $lastSuggestionCount, $selectedSuggestionIndex);
                    }
                } elseif ($key === Cursor::KEY_BACKSPACE || $key === "\x7f") {
                    if ($cursorPos > 0) {
                        $input = mb_substr($input, 0, $cursorPos - 1) . mb_substr($input, $cursorPos);
                        $cursorPos--;
                        $selectedSuggestionIndex = 0;
                        $lastSuggestionCount = $this->redrawAll($input, $cursorPos, $promptLength, $lastSuggestionCount, $selectedSuggestionIndex);
                    }
                } elseif ($key === Cursor::KEY_LEFT) {
                    if ($cursorPos > 0) {
                        $cursorPos--;
                        $this->output->terminal->cursorToColumn($promptLength + $cursorPos);
                    }
                } elseif ($key === Cursor::KEY_RIGHT) {
                    if ($cursorPos < mb_strlen($input)) {
                        $cursorPos++;
                        $this->output->terminal->cursorToColumn($promptLength + $cursorPos);
                    }
                } elseif ($key === Cursor::KEY_ESC || $key === "\e") {
                    // Ignore escape key alone
                    continue;
                } elseif (strlen($key) === 1 && ord($key) >= 32 && ord($key) < 127) {
                    // Printable character
                    $input = mb_substr($input, 0, $cursorPos) . $key . mb_substr($input, $cursorPos);
                    $cursorPos++;
                    $selectedSuggestionIndex = 0;
                    $lastSuggestionCount = $this->redrawAll($input, $cursorPos, $promptLength, $lastSuggestionCount, $selectedSuggestionIndex);
                }
            }

            $this->output->terminal->disableRawMode();

            // Clear all
            if ($lastSuggestionCount > 0) {
                $this->clearAllLines($lastSuggestionCount);
            }

            $this->output->writeln();
            $this->output->success("Sélectionné: {$input}");

            return $input;
        } catch (\Throwable $e) {
            $this->output->terminal->disableRawMode();
            $this->output->terminal->showCursor();
            throw $e;
        }
    }

    /**
     * Ask with simple input (fallback)
     */
    private function askWithInput(): string
    {
        $this->displayQuestion(false);
        $this->output->comment("(Commencez à taper pour voir les suggestions)");

        $prompt = $this->formatPrompt('', $this->default);
        $this->output->write($prompt);

        $input = $this->output->terminal->readLine();

        if ($input === '' && $this->default !== null) {
            return $this->default;
        }

        if ($input === '') {
            $this->output->error("La réponse ne peut pas être vide.");
            return $this->askWithInput();
        }

        $suggestions = $this->findSuggestions($input);

        if (in_array($input, $this->options, true)) {
            return $input;
        }

        if (empty($suggestions)) {
            $confirm = new Confirm($this->output, "Aucune suggestion trouvée. Accepter '{$input}'?", true);
            $accept = $confirm->ask();
            if ($accept) {
                return $input;
            }
            return $this->askWithInput();
        }

        if (count($suggestions) === 1) {
            $confirm = new Confirm($this->output, "Utiliser la suggestion '{$suggestions[0]}'?", true);
            $use = $confirm->ask();
            if ($use) {
                return $suggestions[0];
            }
            return $input;
        }

        $this->output->info("Suggestions trouvées:");
        $choice = new Choice(
            $this->output,
            "Choisissez une option ou tapez 0 pour utiliser votre saisie",
            array_merge(['Utiliser ma saisie: ' . $input], $suggestions),
            1
        );
        $selected = $choice->ask();

        if ($selected === 0) {
            return $input;
        }

        return $suggestions[$selected - 1];
    }

    /**
     * Redraw everything (input + suggestions)
     * @return int Number of suggestion lines drawn
     */
    private function redrawAll(string $input, int $cursorPos, int $promptLength, int $lastSuggestionCount, int $selectedIndex): int
    {
        // Clear previous display (go back to start of input line, then clear all lines)
        if ($lastSuggestionCount > 0) {
            $this->clearAllLines($lastSuggestionCount);
        }

        // Move to beginning of line and clear
        $this->output->terminal->cursorToColumn(0);
        $this->output->terminal->clearLine();

        // Redraw prompt and input
        $prompt = $this->formatPrompt('', $this->default);
        $this->output->write($prompt . $input);

        // Get new suggestions
        $suggestions = $this->findSuggestions($input);

        // Display suggestions if any
        if (!empty($suggestions)) {
            $this->output->writeln();
            $this->output->writeln(Ansi::DIM . "  Suggestions:" . Ansi::RESET);

            for ($i = 0; $i < count($suggestions); $i++) {
                $highlighted = ($i === $selectedIndex);
                if ($highlighted) {
                    $this->output->writeln(Ansi::FG_GREEN . Ansi::BOLD . "  » " . $suggestions[$i] . Ansi::RESET);
                } else {
                    $this->output->writeln(Ansi::DIM . "    " . $suggestions[$i] . Ansi::RESET);
                }
            }

            // Move cursor back to input position (up by suggestion count + header)
            $linesToGoUp = count($suggestions) + 1;
            for ($i = 0; $i < $linesToGoUp; $i++) {
                $this->output->terminal->cursorUp();
            }
            $this->output->terminal->cursorToColumn($promptLength + $cursorPos);

            return $linesToGoUp;
        }

        // No suggestions, just position cursor
        $this->output->terminal->cursorToColumn($promptLength + $cursorPos);
        return 0;
    }

    /**
     * Clear all lines (suggestions)
     */
    private function clearAllLines(int $count): void
    {
        // Save position
        $this->output->terminal->saveCursor();

        // Go down and clear each line
        for ($i = 0; $i < $count; $i++) {
            $this->output->terminal->cursorDown(1);
            $this->output->terminal->clearLine();
        }

        // Restore position
        $this->output->terminal->restoreCursor();
    }

    /**
     * Find suggestions matching the input
     * @return array<string>
     */
    private function findSuggestions(string $input): array
    {
        if ($input === '') {
            return array_slice($this->options, 0, $this->maxSuggestions);
        }

        $input = strtolower($input);
        $suggestions = [];

        // First, find options that start with the input
        foreach ($this->options as $option) {
            if (str_starts_with(strtolower($option), $input)) {
                $suggestions[] = $option;
            }
        }

        // If not enough, find options that contain the input
        if (count($suggestions) < $this->maxSuggestions) {
            foreach ($this->options as $option) {
                if (!in_array($option, $suggestions) && str_contains(strtolower($option), $input)) {
                    $suggestions[] = $option;
                }
            }
        }

        return array_slice($suggestions, 0, $this->maxSuggestions);
    }
}
