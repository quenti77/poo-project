<?php

namespace Tuto\CLI\Output;

class Cursor
{
    // Cursor control codes
    public const string HIDE = "\033[?25l";
    public const string SHOW = "\033[?25h";
    public const string SAVE = "\033[s";
    public const string RESTORE = "\033[u";
    public const string CLEAR_LINE = "\033[2K";

    // Keyboard input keys
    public const string KEY_UP = "\e[A";
    public const string KEY_DOWN = "\e[B";
    public const string KEY_RIGHT = "\e[C";
    public const string KEY_LEFT = "\e[D";
    public const string KEY_ENTER = "\n";
    public const string KEY_SPACE = " ";
    public const string KEY_TAB = "\t";
    public const string KEY_BACKSPACE = "\x7f";
    public const string KEY_ESC = "\e";

    /**
     * @param int $row Ligne (1-indexed)
     * @param int $col Colonne (1-indexed)
     * @return string
     */
    public static function moveTo(int $row, int $col): string
    {
        return "\033[{$row};{$col}H";
    }

    /**
     * @param int $n Nombre de lignes
     * @return string
     */
    public static function moveUp(int $n = 1): string
    {
        return "\033[{$n}A";
    }

    /**
     * @param int $n Nombre de lignes
     * @return string
     */
    public static function moveDown(int $n = 1): string
    {
        return "\033[{$n}B";
    }

    /**
     * @param int $n Nombre de colonnes
     * @return string
     */
    public static function moveRight(int $n = 1): string
    {
        return "\033[{$n}C";
    }

    /**
     * @param int $n Nombre de colonnes
     * @return string
     */
    public static function moveLeft(int $n = 1): string
    {
        return "\033[{$n}D";
    }

    /**
     * @return string
     */
    public static function saveCursor(): string
    {
        return "\033[s";
    }

    /**
     * @return string
     */
    public static function restoreCursor(): string
    {
        return "\033[u";
    }

    /**
     * @return string
     */
    public static function hideCursor(): string
    {
        return self::HIDE;
    }

    /**
     * @return string
     */
    public static function showCursor(): string
    {
        return self::SHOW;
    }

    public static function clearLine(): string
    {
        return self::CLEAR_LINE;
    }

    /**
     * Move cursor to specific column (1-indexed)
     */
    public static function toColumn(int $column): string
    {
        return "\e[{$column}G";
    }
}