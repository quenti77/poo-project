<?php

namespace Tuto\CLI\Components;

use Tuto\CLI\Ansi;
use Tuto\CLI\Output;

class Question
{
    private Output $out;
    private string $question;
    private string|null $default;
    private mixed $validator;

    public function __construct(
        Output $out,
        string $question,
        string|null $default = null
    ) {
        $this->out = $out;
        $this->question = $question;
        $this->default = $default;
        $this->validator = null;
    }

    /**
     * Set a validator callback
     * @param callable(string): bool|string $validator Return true if valid, error message if invalid
     */
    public function setValidator(callable $validator): self
    {
        $this->validator = $validator;
        return $this;
    }

    public function ask(): string
    {
        while (true) {
            $prompt = $this->formatPrompt();
            $this->out->write($prompt);

            $input = trim(fgets(STDIN) ?: '');

            if ($input === '' && $this->default !== null) {
                return $this->default;
            }

            if ($input === '' && $this->default === null) {
                $this->out->error("La réponse ne peut pas être vide.");
                continue;
            }

            if ($this->validator !== null) {
                $result = ($this->validator)($input);
                if ($result === true) {
                    return $input;
                }
                $this->out->error(is_string($result) ? $result : "Valeur invalide.");
                continue;
            }

            return $input;
        }
    }

    private function formatPrompt(): string
    {
        $prompt = Ansi::FG_CYAN . $this->question . Ansi::RESET;

        if ($this->default !== null) {
            $defaultText = Ansi::DIM . "[{$this->default}]" . Ansi::RESET;
            $prompt .= " {$defaultText}";
        }

        $prompt .= ": ";

        return $prompt;
    }
}
