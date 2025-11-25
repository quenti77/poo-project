<?php

namespace Tuto\CLI;

class Ansi
{
    public const string RESET = "\e[0m";

    // Styles
    public const string BOLD = "\e[1m";
    public const string DIM = "\e[2m";
    public const string ITALIC = "\e[3m";
    public const string UNDERLINE = "\e[4m";
    public const string INVERT = "\e[7m";
    public const string HIDDEN = "\e[8m";
    public const string STRIKE = "\e[9m";

    // Couleurs standard (foreground)
    public const string FG_BLACK = "\e[30m";
    public const string FG_RED = "\e[31m";
    public const string FG_GREEN = "\e[32m";
    public const string FG_YELLOW = "\e[33m";
    public const string FG_BLUE = "\e[34m";
    public const string FG_MAGENTA = "\e[35m";
    public const string FG_CYAN = "\e[36m";
    public const string FG_WHITE = "\e[37m";

    // Background
    public const string BG_BLACK = "\e[40m";
    public const string BG_RED = "\e[41m";
    public const string BG_GREEN = "\e[42m";
    public const string BG_YELLOW = "\e[43m";
    public const string BG_BLUE = "\e[44m";
    public const string BG_MAGENTA = "\e[45m";
    public const string BG_CYAN = "\e[46m";
    public const string BG_WHITE = "\e[47m";

    // Truecolor
    public static function fgRgb(int $r, int $g, int $b): string
    {
        return "\e[38;2;{$r};{$g};{$b}m";
    }

    public static function bgRgb(int $r, int $g, int $b): string
    {
        return "\e[48;2;{$r};{$g};{$b}m";
    }

    // 256 couleurs
    public static function fg256(int $code): string
    {
        return "\e[38;5;{$code}m";
    }

    public static function bg256(int $code): string
    {
        return "\e[48;5;{$code}m";
    }
}
