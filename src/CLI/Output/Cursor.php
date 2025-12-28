<?php

namespace Tuto\CLI\Output;

class Cursor
{
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
        return "\033[?25l";
    }

    /**
     * @return string
     */
    public static function showCursor(): string
    {
        return "\033[?25h";
    }

    public static function clearLine(): string
    {
        return "\033[2K";
    }
}