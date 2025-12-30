<?php

namespace Tuto\CLIOld\Input;

use Tuto\CLIOld\Output\Ansi;
use Tuto\CLIOld\Output\Cursor;
use Tuto\CLIOld\Output\Output;

class Select extends AbstractInput
{
    /** @var array<int|string, string> */
    private array $options;
    private string|int|null $default;
    private bool $searchable;
    private bool $useKeyboardNavigation;

    /**
     * @param array<int|string, string> $options
     */
    public function __construct(
        Output $out,
        string $question,
        array $options,
        string|int|null $default = null,
        bool $searchable = false,
        bool $useKeyboardNavigation = true
    ) {
        parent::__construct($out, $question);
        $this->options = $options;
        $this->default = $default;
        $this->searchable = $searchable;
        $this->useKeyboardNavigation = $useKeyboardNavigation && $this->output->terminal->supportsInteractive();
    }

    /**
     * Ask and return the selected key
     */
    public function ask(): string|int
    {
        if ($this->useKeyboardNavigation) {
            return $this->askWithKeyboard();
        }

        return $this->askWithInput();
    }

    /**
     * Ask using keyboard navigation (arrows + enter)
     */
    private function askWithKeyboard(): string|int
    {
        $keys = array_keys($this->options);
        $selectedIndex = $this->getDefaultIndex($keys);

        $this->displayQuestion();
        $this->output->comment("(Utilisez ↑↓ pour naviguer, Entrée pour sélectionner)");
        $this->output->writeln();

        $this->output->terminal->hideCursor();
        $this->output->terminal->enableRawMode();

        try {
            while (true) {
                $this->displayOptionsWithHighlight($selectedIndex);

                $key = $this->output->terminal->readKey();

                if ($key === Cursor::KEY_UP) {
                    $selectedIndex = ($selectedIndex - 1 + count($keys)) % count($keys);
                    $this->clearOptions(count($keys));
                } elseif ($key === Cursor::KEY_DOWN) {
                    $selectedIndex = ($selectedIndex + 1) % count($keys);
                    $this->clearOptions(count($keys));
                } elseif ($key === Cursor::KEY_ENTER || $key === "\r") {
                    break;
                }
            }

            $this->output->terminal->disableRawMode();
            $this->output->terminal->showCursor();

            $selectedKey = $keys[$selectedIndex];
            $this->output->writeln();
            $this->output->success("Sélectionné: {$this->options[$selectedKey]}");

            return $selectedKey;
        } catch (\Throwable $e) {
            $this->output->terminal->disableRawMode();
            $this->output->terminal->showCursor();
            throw $e;
        }
    }

    /**
     * Ask using text input (fallback)
     */
    private function askWithInput(): string|int
    {
        $this->displayQuestion();
        $this->displayOptionsSimple();

        while (true) {
            $prompt = $this->formatPrompt('Votre choix', $this->default);
            $this->output->write($prompt);

            $input = $this->output->terminal->readLine();

            if ($input === '' && $this->default !== null) {
                return $this->default;
            }

            // Try to select by number
            $numericInput = filter_var($input, FILTER_VALIDATE_INT);
            if ($numericInput !== false) {
                $keys = array_keys($this->options);
                $index = $numericInput - 1;
                if (isset($keys[$index])) {
                    return $keys[$index];
                }
            }

            // Try to select by exact key
            if (array_key_exists($input, $this->options)) {
                return $input;
            }

            // Try to search by value if searchable
            if ($this->searchable) {
                $matches = $this->searchOptions($input);
                if (count($matches) === 1) {
                    $key = array_key_first($matches);
                    $this->output->success("Sélectionné: {$matches[$key]}");
                    return $key;
                } elseif (count($matches) > 1) {
                    $this->output->warning("Plusieurs correspondances trouvées:");
                    $this->displayFilteredOptions($matches);
                    continue;
                }
            }

            $this->output->error("Sélection invalide. Veuillez entrer un numéro ou une clé valide.");
        }
    }

    /**
     * Ask and return the selected value
     */
    public function askValue(): string
    {
        $key = $this->ask();
        return $this->options[$key];
    }

    /**
     * Display options with highlighting for keyboard navigation
     */
    private function displayOptionsWithHighlight(int $selectedIndex): void
    {
        $index = 0;
        foreach ($this->options as $key => $value) {
            $number = $index + 1;
            $highlighted = ($index === $selectedIndex);
            $isDefault = ($key === $this->default && !$highlighted);

            $line = $this->formatOption($number, $value, $key, $isDefault, $highlighted);
            $this->output->writeln($line);
            $index++;
        }
    }

    /**
     * Display options without highlighting
     */
    private function displayOptionsSimple(): void
    {
        if ($this->searchable) {
            $this->output->comment("(Tapez pour rechercher ou entrez un numéro)");
        }
        $this->output->writeln();

        $index = 0;
        foreach ($this->options as $key => $value) {
            $number = $index + 1;
            $isDefault = ($key === $this->default);

            $line = $this->formatOption($number, $value, $key, $isDefault);
            $this->output->writeln($line);
            $index++;
        }
        $this->output->writeln();
    }

    /**
     * @param array<int|string, string> $options
     */
    private function displayFilteredOptions(array $options): void
    {
        $index = 1;
        foreach ($options as $key => $value) {
            $line = $this->formatOption($index, $value);
            $this->output->writeln($line);
            $index++;
        }
    }

    /**
     * Clear N lines (for redrawing options)
     */
    private function clearOptions(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            $this->output->terminal->cursorUp();
            $this->output->terminal->clearLine();
        }
    }

    /**
     * Get the index of the default option
     * @param array<int|string> $keys
     */
    private function getDefaultIndex(array $keys): int
    {
        if ($this->default !== null) {
            $index = array_search($this->default, $keys, true);
            if ($index !== false) {
                return $index;
            }
        }
        return 0;
    }

    /**
     * @return array<int|string, string>
     */
    private function searchOptions(string $query): array
    {
        $query = strtolower($query);
        $matches = [];

        foreach ($this->options as $key => $value) {
            if (str_contains(strtolower($value), $query)) {
                $matches[$key] = $value;
            }
        }

        return $matches;
    }
}
