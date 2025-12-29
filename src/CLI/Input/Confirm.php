<?php

namespace Tuto\CLI\Input;

use Tuto\CLI\Output\Ansi;
use Tuto\CLI\Output\Output;

class Confirm
{
    private Output $output;
    private string $question;
    private bool $default;

    public function __construct(
        Output $out,
        string $question,
        bool $default = false
    ) {
        $this->output = $out;
        $this->question = $question;
        $this->default = $default;
    }

    public function ask(): bool
    {
        while (true) {
            $prompt = $this->formatPrompt();
            $this->output->write($prompt);

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

            $this->output->error("Veuillez répondre par 'y' (oui) ou 'n' (non).");
        }
    }

    private function formatPrompt(): string
    {
        $yesNo = $this->default ? 'Y/n' : 'y/N';
        $options = Ansi::DIM . "[{$yesNo}]" . Ansi::RESET;

        return Ansi::FG_CYAN . $this->question . Ansi::RESET . " {$options}: ";
    }
}
