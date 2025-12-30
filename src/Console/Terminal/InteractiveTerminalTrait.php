<?php

namespace Tuto\Console\Terminal;

use Tuto\Console\Terminal\Actions\CursorActionTrait;
use Tuto\Console\Terminal\Actions\KeyActionTrait;

trait InteractiveTerminalTrait
{
    use CursorActionTrait;
    use KeyActionTrait;
    use ScreenTerminalTrait;

    private bool $rawModeEnabled = false;

    /**
     * @return bool
     */
    public function rawModeEnabled(): bool
    {
        return $this->rawModeEnabled;
    }

    /**
     * @return void
     */
    public function enableRawMode(): void
    {
        if ($this->rawModeEnabled) {
            return;
        }

        if ($this->isWindows()) {
            return;
        }

        shell_exec('stty -icanon -echo');
        $this->rawModeEnabled = true;
    }

    /**
     * @return void
     */
    public function disableRawMode(): void
    {
        if (!$this->rawModeEnabled) {
            return;
        }

        if ($this->isWindows()) {
            return;
        }

        shell_exec('stty icanon echo');
        $this->rawModeEnabled = false;
    }

    public function readKey(): string
    {
        if ($this->rawModeEnabled === false) {
            return '';
        }
        $key = fgetc(STDIN) ?: '';
        if ($key !== "\e") {
            return $key;
        }
        $key .= fgetc(STDIN) ?: '';
        if ($key !== "\e[") {
            return $key;
        }
        $key .= fgetc(STDIN) ?: '';
        if ($key !== "\e[3") {
            return $key;
        }
        return $key . fgetc(STDIN) ?: '';
    }

    /**
     * @return string
     */
    public function readLine(): string
    {
        return trim(fgets(STDIN) ?: '');
    }
}