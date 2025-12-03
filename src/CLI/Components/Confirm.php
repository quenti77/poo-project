<?php

namespace Tuto\CLI\Components;

use Tuto\CLI\Ansi;
use Tuto\CLI\Output;

class Confirm
{
    private Output $out;
    private string $question;
    private bool $default;

    public function __construct(
        Output $out,
        string $question,
        bool $default = false
    ) {
        $this->out = $out;
        $this->question = $question;
        $this->default = $default;
    }

    public function ask(): bool
    {
        while (true) {
            $prompt = $this->formatPrompt();
            $this->out->write($prompt);

            $input = strtolower(trim(fgets(STDIN) ?: ''));

            if ($input === '') {
                return $this->default;
            }

            if (in_array($input, ['y', 'yes', 'oui', 'o'])) {
                return true;
            }

            if (in_array($input, ['n', 'no', 'non'])) {
                return false;
            }

            $this->out->error("Veuillez répondre par 'y' (oui) ou 'n' (non).");
        }
    }

    private function formatPrompt(): string
    {
        $yesNo = $this->default ? 'Y/n' : 'y/N';
        $options = Ansi::DIM . "[{$yesNo}]" . Ansi::RESET;

        return Ansi::FG_CYAN . $this->question . Ansi::RESET . " {$options}: ";
    }
}
