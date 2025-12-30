<?php

namespace Tuto\Console\Terminal;

use Tuto\Utils\ValueObject\Size;

trait ScreenTerminalTrait
{
    private Size|null $terminalSize = null;

    /**
     * @return Size
     */
    public function getTerminalSize(): Size
    {
        return $this->terminalSize ?? new Size(80, 24);
    }

    /**
     * @return void
     */
    public function updateTerminalSize(): void
    {
        $terminalSize = $this->isWindows()
            ? $this->getWindowsTerminalSize()
            : $this->getUnixTerminalSize();

        $this->terminalSize = $terminalSize ?? new Size(80, 24);
    }

    /**
     * @return bool
     */
    public function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\' || PHP_OS_FAMILY === 'Windows';
    }

    /**
     * @return Size|null
     */
    private function getWindowsTerminalSize(): Size|null
    {
        // PowerShell
        $output = shell_exec('powershell -Command "$host.UI.RawUI.WindowSize.Width; $host.UI.RawUI.WindowSize.Height"');

        if ($output) {
            $lines = array_filter(explode("\n", trim($output)));
            if (count($lines) >= 2) {
                return new Size(trim($lines[0]), trim($lines[1]));
            }
        }

        // Alternative : mode
        $output = shell_exec('mode con');
        if (preg_match('/Columns:\s*(\d+)/i', $output, $widthMatch) &&
            preg_match('/Lines:\s*(\d+)/i', $output, $heightMatch)) {

            return new Size($widthMatch[0], $heightMatch[1]);
        }

        return null;
    }

    /**
     * @return Size|null
     */
    private function getUnixTerminalSize(): Size|null
    {
        // Méthode 1 : Via la commande tput (plus fiable)
        $width = (int) trim(shell_exec('tput cols 2>/dev/null') ?: '0');
        $height = (int) trim(shell_exec('tput lines 2>/dev/null') ?: '0');

        if ($width > 0 && $height > 0) {
            return new Size($width, $height);
        }

        // Méthode 2 : Via stty
        $output = shell_exec('stty size 2>/dev/null');
        if ($output && preg_match('/(\d+)\s+(\d+)/', $output, $matches)) {
            return new Size($matches[2], $matches[1]);
        }

        return null;
    }
}