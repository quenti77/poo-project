<?php

namespace Tuto\CLI\Components;

use Tuto\CLI\Ansi;
use Tuto\CLI\Output;

class Password
{
    private Output $out;
    private string $question;
    private bool $requireConfirmation;
    private int|null $minLength;

    public function __construct(
        Output $out,
        string $question,
        bool $requireConfirmation = false,
        int|null $minLength = null
    ) {
        $this->out = $out;
        $this->question = $question;
        $this->requireConfirmation = $requireConfirmation;
        $this->minLength = $minLength;
    }

    public function ask(): string
    {
        while (true) {
            $password = $this->readPassword($this->question);

            if ($password === '') {
                $this->out->error("Le mot de passe ne peut pas être vide.");
                continue;
            }

            if ($this->minLength !== null && strlen($password) < $this->minLength) {
                $this->out->error("Le mot de passe doit contenir au moins {$this->minLength} caractères.");
                continue;
            }

            if ($this->requireConfirmation) {
                $confirmation = $this->readPassword("Confirmer le mot de passe");

                if ($password !== $confirmation) {
                    $this->out->error("Les mots de passe ne correspondent pas.");
                    continue;
                }
            }

            return $password;
        }
    }

    private function readPassword(string $prompt): string
    {
        $formattedPrompt = Ansi::FG_CYAN . $prompt . Ansi::RESET . ": ";
        $this->out->write($formattedPrompt);

        if ($this->isWindows()) {
            return $this->readPasswordWindows();
        }

        return $this->readPasswordUnix();
    }

    private function readPasswordUnix(): string
    {
        shell_exec('stty -echo');
        $password = trim(fgets(STDIN) ?: '');
        shell_exec('stty echo');
        $this->out->writeln();

        return $password;
    }

    private function readPasswordWindows(): string
    {
        $password = '';
        while (true) {
            $char = fgetc(STDIN);

            if ($char === "\n" || $char === "\r") {
                break;
            }

            if ($char === "\x7f" || $char === "\x08") {
                if (strlen($password) > 0) {
                    $password = substr($password, 0, -1);
                }
                continue;
            }

            $password .= $char;
        }

        $this->out->writeln();
        return $password;
    }

    private function isWindows(): bool
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }
}
