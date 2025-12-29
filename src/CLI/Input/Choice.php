<?php

namespace Tuto\CLI\Input;

use Tuto\CLI\Output\Ansi;
use Tuto\CLI\Output\Output;

class Choice
{
    private Output $output;
    private string $question;
    /** @var array<string> */
    private array $choices;
    private string|int|null $default;

    /**
     * @param array<int|string, string> $choices Can be indexed array or associative
     */
    public function __construct(
        Output $out,
        string $question,
        array $choices,
        string|int|null $default = null
    ) {
        $this->output = $out;
        $this->question = $question;
        $this->choices = $choices;
        $this->default = $default;
    }

    /**
     * Ask the user to choose and return the key
     */
    public function ask(): string|int
    {
        $this->displayQuestion();
        $this->displayChoices();

        while (true) {
            $prompt = $this->formatPrompt();
            $this->output->write($prompt);

            $input = trim(fgets(STDIN) ?: '');

            if ($input === '' && $this->default !== null) {
                return $this->default;
            }

            if (array_key_exists($input, $this->choices)) {
                return $input;
            }

            $numericInput = filter_var($input, FILTER_VALIDATE_INT);
            if ($numericInput !== false) {
                $keys = array_keys($this->choices);
                $index = $numericInput - 1;
                if (isset($keys[$index])) {
                    return $keys[$index];
                }
            }

            $this->output->error("Choix invalide. Veuillez entrer un numéro ou une clé valide.");
        }
    }

    /**
     * Ask the user to choose and return the value
     */
    public function askValue(): string
    {
        $key = $this->ask();
        return $this->choices[$key];
    }

    private function displayQuestion(): void
    {
        $this->output->writeln(Ansi::FG_CYAN . $this->question . Ansi::RESET);
    }

    private function displayChoices(): void
    {
        $index = 1;
        foreach ($this->choices as $key => $value) {
            $number = Ansi::FG_YELLOW . "[{$index}]" . Ansi::RESET;
            $keyDisplay = is_string($key) ? Ansi::DIM . " ({$key})" . Ansi::RESET : "";
            $defaultMarker = ($key === $this->default) ? Ansi::FG_GREEN . " ✓" . Ansi::RESET : "";

            $this->output->writeln("  {$number} {$value}{$keyDisplay}{$defaultMarker}");
            $index++;
        }
    }

    private function formatPrompt(): string
    {
        $defaultText = "";
        if ($this->default !== null) {
            $defaultText = Ansi::DIM . " [{$this->default}]" . Ansi::RESET;
        }

        return Ansi::FG_CYAN . "Votre choix" . Ansi::RESET . "{$defaultText}: ";
    }
}
