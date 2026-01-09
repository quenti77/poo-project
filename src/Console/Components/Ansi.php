<?php

namespace Tuto\Console\Components;

use Tuto\Utils\EasyEnum;

enum Ansi: string
{
    use EasyEnum;

    case RESET = "\e[0m";

    // Styles
    case BOLD = "\e[1m";
    case DIM = "\e[2m";
    case ITALIC = "\e[3m";
    case UNDERLINE = "\e[4m";
    case INVERT = "\e[7m";
    case HIDDEN = "\e[8m";
    case STRIKE = "\e[9m";

    // Standard Foreground
    case FG_BLACK = "\e[30m";
    case FG_RED = "\e[31m";
    case FG_GREEN = "\e[32m";
    case FG_YELLOW = "\e[33m";
    case FG_BLUE = "\e[34m";
    case FG_MAGENTA = "\e[35m";
    case FG_CYAN = "\e[36m";
    case FG_WHITE = "\e[37m";

    // Standard Background
    case BG_BLACK = "\e[40m";
    case BG_RED = "\e[41m";
    case BG_GREEN = "\e[42m";
    case BG_YELLOW = "\e[43m";
    case BG_BLUE = "\e[44m";
    case BG_MAGENTA = "\e[45m";
    case BG_CYAN = "\e[46m";
    case BG_WHITE = "\e[47m";

    // True-color

    /**
     * @param int $r
     * @param int $g
     * @param int $b
     * @return string
     */
    public static function fgRgb(int $r, int $g, int $b): string
    {
        return "\e[38;2;{$r};{$g};{$b}m";
    }

    /**
     * @param int $r
     * @param int $g
     * @param int $b
     * @return string
     */
    public static function bgRgb(int $r, int $g, int $b): string
    {
        return "\e[48;2;{$r};{$g};{$b}m";
    }

    // 256 couleurs

    /**
     * @param int $code
     * @return string
     */
    public static function fg256(int $code): string
    {
        return "\e[38;5;{$code}m";
    }

    /**
     * @param int $code
     * @return string
     */
    public static function bg256(int $code): string
    {
        return "\e[48;5;{$code}m";
    }
}
