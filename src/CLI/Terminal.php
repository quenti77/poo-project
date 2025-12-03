<?php

namespace Tuto\CLI;

class Terminal
{
    public const string KEY_UP = "\e[A";
    public const string KEY_DOWN = "\e[B";
    public const string KEY_RIGHT = "\e[C";
    public const string KEY_LEFT = "\e[D";
    public const string KEY_ENTER = "\n";
    public const string KEY_SPACE = " ";
    public const string KEY_BACKSPACE = "\x7f";
    public const string KEY_DELETE = "\e[3~";
    public const string KEY_ESC = "\e";
    public const string KEY_TAB = "\t";

    private static bool $rawMode = false;

    /**
     * Enable raw mode (disable line buffering and echo)
     */
    public static function enableRawMode(): void
    {
        if (self::$rawMode) {
            return;
        }

        if (self::isWindows()) {
            // Windows ne supporte pas facilement le mode raw
            return;
        }

        shell_exec('stty -icanon -echo');
        self::$rawMode = true;
    }

    /**
     * Disable raw mode (restore normal terminal)
     */
    public static function disableRawMode(): void
    {
        if (!self::$rawMode) {
            return;
        }

        if (self::isWindows()) {
            return;
        }

        shell_exec('stty icanon echo');
        self::$rawMode = false;
    }

    /**
     * Read a single key from stdin (requires raw mode)
     */
    public static function readKey(): string
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
     * Read a line from stdin
     */
    public static function readLine(): string
    {
        return trim(fgets(STDIN) ?: '');
    }

    /**
     * Move cursor up by N lines
     */
    public static function cursorUp(int $lines = 1): void
    {
        echo "\e[{$lines}A";
    }

    /**
     * Move cursor down by N lines
     */
    public static function cursorDown(int $lines = 1): void
    {
        echo "\e[{$lines}B";
    }

    /**
     * Move cursor to column
     */
    public static function cursorToColumn(int $column = 0): void
    {
        echo "\e[{$column}G";
    }

    /**
     * Clear current line
     */
    public static function clearLine(): void
    {
        echo "\e[2K";
    }

    /**
     * Clear from cursor to end of line
     */
    public static function clearToEnd(): void
    {
        echo "\e[K";
    }

    /**
     * Hide cursor
     */
    public static function hideCursor(): void
    {
        echo "\e[?25l";
    }

    /**
     * Show cursor
     */
    public static function showCursor(): void
    {
        echo "\e[?25h";
    }

    /**
     * Save cursor position
     */
    public static function saveCursor(): void
    {
        echo "\e[s";
    }

    /**
     * Restore cursor position
     */
    public static function restoreCursor(): void
    {
        echo "\e[u";
    }

    /**
     * Clear entire screen
     */
    public static function clearScreen(): void
    {
        echo "\e[2J\e[H";
    }

    /**
     * Get terminal width
     */
    public static function getWidth(): int
    {
        if (self::isWindows()) {
            return 80; // Default width for Windows
        }

        $output = shell_exec('tput cols');
        return (int) ($output ?: 80);
    }

    /**
     * Get terminal height
     */
    public static function getHeight(): int
    {
        if (self::isWindows()) {
            return 24; // Default height for Windows
        }

        $output = shell_exec('tput lines');
        return (int) ($output ?: 24);
    }

    /**
     * Check if running on Windows
     */
    public static function isWindows(): bool
    {
        return PHP_OS_FAMILY === 'Windows';
    }

    /**
     * Check if running in WSL (Windows Subsystem for Linux)
     */
    public static function isWSL(): bool
    {
        if (self::isWindows()) {
            return false;
        }

        // Check if running in WSL
        $osRelease = @file_get_contents('/proc/version');
        return $osRelease !== false &&
            (stripos($osRelease, 'microsoft') !== false || stripos($osRelease, 'WSL') !== false);
    }

    /**
     * Check if terminal supports interactive features (raw mode, cursor control)
     */
    public static function supportsInteractive(): bool
    {
        // Disable on Windows and WSL (cursor control issues)
        return !(self::isWindows() || self::isWSL());
    }

    /**
     * Check if terminal supports colors
     */
    public static function supportsColor(): bool
    {
        if (self::isWindows()) {
            return getenv('ANSICON') !== false || getenv('ConEmuANSI') === 'ON';
        }

        return function_exists('posix_isatty') && posix_isatty(STDOUT);
    }
}
