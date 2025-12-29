<?php

namespace Tuto\CLI;

use Tuto\CLI\Output\Cursor;

class Terminal
{
    // Key constants
    public const string KEY_UP = "\e[A";
    public const string KEY_DOWN = "\e[B";
    public const string KEY_RIGHT = "\e[C";
    public const string KEY_LEFT = "\e[D";
    public const string KEY_ENTER = "\n";
    public const string KEY_SPACE = " ";
    public const string KEY_TAB = "\t";
    public const string KEY_BACKSPACE = "\x7f";
    public const string KEY_ESC = "\e";

    public bool $ansiEnabled = false;
    public bool $colorSupport = false;
    public bool $rawMode = false;
    public int $width = 80;
    public int $height = 24;

    public function init(): void
    {
        if (DIRECTORY_SEPARATOR === '\\') {
            // Windows
            if (function_exists('sapi_windows_vt100_support')) {
                $this->ansiEnabled = sapi_windows_vt100_support(STDOUT, true);
                sapi_windows_vt100_support(STDERR, true);
            }
        } else {
            // Unix-like
            $this->ansiEnabled = true;
        }

        $this->colorSupport = $this->supportsColor();
        $this->updateTerminalSize();
        // Ne pas activer le mode raw au démarrage - seulement quand nécessaire
    }

    /**
     * Destructor - ensure terminal is restored to normal mode
     */
    public function __destruct()
    {
        $this->disableRawMode();
        $this->showCursor();
    }

    /**
     * @return void
     */
    public function enableRawMode(): void
    {
        if ($this->rawMode) {
            return;
        }

        if ($this->isWindows()) {
            return;
        }

        shell_exec('stty -icanon -echo');
        $this->rawMode = true;
    }

    /**
     * Disable raw mode (restore normal terminal)
     */
    public function disableRawMode(): void
    {
        if (!$this->rawMode) {
            return;
        }

        if ($this->isWindows()) {
            return;
        }

        shell_exec('stty icanon echo');
        $this->rawMode = false;
    }

    /**
     * @return string
     */
    public function readKey(): string
    {
        $key = fgetc(STDIN);

        // Check for escape sequences (arrow keys, etc.)
        if ($key === "\e") {
            $key .= fgetc(STDIN);
            if ($key === "\e[") {
                $key .= fgetc(STDIN);
                // Some keys like delete have additional characters
                if ($key === "\e[3") {
                    $key .= fgetc(STDIN);
                }
            }
        }

        return $key;
    }

    /**
     * @return string
     */
    public function readLine(): string
    {
        return trim(fgets(STDIN) ?: '');
    }

    /**
     * @return void
     */
    private function updateTerminalSize(): void
    {
        $size = $this->isWindows() ? $this->getTerminalSizeWindows() : $this->getTerminalSize();
        $size ??= ['width' => 80, 'height' => 24];

        $this->width = $size['width'];
        $this->height = $size['height'];
    }

    /**
     * @return bool
     */
    private function isWindows(): bool
    {
        return DIRECTORY_SEPARATOR === '\\' || PHP_OS_FAMILY === 'Windows';
    }

    /**
     * @return int[]|null
     */
    private function getTerminalSize(): array|null
    {
        // Méthode 1 : Via la commande tput (plus fiable)
        $width = (int) trim(shell_exec('tput cols 2>/dev/null') ?: '0');
        $height = (int) trim(shell_exec('tput lines 2>/dev/null') ?: '0');

        if ($width > 0 && $height > 0) {
            return ['width' => $width, 'height' => $height];
        }

        // Méthode 2 : Via stty
        $output = shell_exec('stty size 2>/dev/null');
        if ($output && preg_match('/(\d+)\s+(\d+)/', $output, $matches)) {
            return [
                'width' => (int) $matches[2],
                'height' => (int) $matches[1],
            ];
        }

        return null;
    }

    /**
     * @return int[]|null
     */
    private function getTerminalSizeWindows(): array|null
    {
        // PowerShell
        $output = shell_exec('powershell -Command "$host.UI.RawUI.WindowSize.Width; $host.UI.RawUI.WindowSize.Height"');

        if ($output) {
            $lines = array_filter(explode("\n", trim($output)));
            if (count($lines) >= 2) {
                return [
                    'width' => (int) trim($lines[0]),
                    'height' => (int) trim($lines[1]),
                ];
            }
        }

        // Alternative : mode
        $output = shell_exec('mode con');
        if (preg_match('/Columns:\s*(\d+)/i', $output, $widthMatch) &&
            preg_match('/Lines:\s*(\d+)/i', $output, $heightMatch)) {
            return [
                'width' => (int) $widthMatch[1],
                'height' => (int) $heightMatch[1],
            ];
        }

        return null;
    }

    /**
     * @return bool
     */
    private function supportsColor(): bool
    {
        // Environment variables
        if (getenv('NO_COLOR') !== false) {
            return false;
        }

        if (getenv('FORCE_COLOR') !== false || getenv('COLORTERM') !== false) {
            return true;
        }

        // Windows 10+ ANSI is supported
        if (DIRECTORY_SEPARATOR === '\\') {
            return PHP_VERSION_ID >= 70200; // PHP 7.2+
        }

        // Unix : Check if STDOUT is a TTY
        if (function_exists('posix_isatty')) {
            return posix_isatty(STDOUT);
        }

        // Check TERM
        $term = getenv('TERM');
        return $term && $term !== 'dumb';
    }

    /**
     * Check if terminal supports interactive mode
     */
    public function supportsInteractive(): bool
    {
        return $this->ansiEnabled && !$this->isWindows() && function_exists('posix_isatty') && posix_isatty(STDIN);
    }

    /**
     * Hide cursor
     */
    public function hideCursor(): void
    {
        if ($this->ansiEnabled) {
            echo Cursor::HIDE;
        }
    }

    /**
     * Show cursor
     */
    public function showCursor(): void
    {
        if ($this->ansiEnabled) {
            echo Cursor::SHOW;
        }
    }

    /**
     * Move cursor up by N lines
     */
    public function cursorUp(int $n = 1): void
    {
        if ($this->ansiEnabled && $n > 0) {
            echo Cursor::moveUp($n);
        }
    }

    /**
     * Move cursor down by N lines
     */
    public function cursorDown(int $n = 1): void
    {
        if ($this->ansiEnabled && $n > 0) {
            echo Cursor::moveDown($n);
        }
    }

    /**
     * Move cursor to column N
     */
    public function cursorToColumn(int $column): void
    {
        if ($this->ansiEnabled) {
            echo Cursor::toColumn($column);
        }
    }

    /**
     * Clear current line
     */
    public function clearLine(): void
    {
        if ($this->ansiEnabled) {
            echo Cursor::CLEAR_LINE;
        }
    }

    /**
     * Save cursor position
     */
    public function saveCursor(): void
    {
        if ($this->ansiEnabled) {
            echo Cursor::SAVE;
        }
    }

    /**
     * Restore cursor position
     */
    public function restoreCursor(): void
    {
        if ($this->ansiEnabled) {
            echo Cursor::RESTORE;
        }
    }
}
