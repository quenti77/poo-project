<?php

namespace Tuto\Console\Terminal\Actions;

use Tuto\Utils\EasyEnum;

enum Key: string
{
    use EasyEnum;

    case KEY_UP = "\e[{n}A";
    case KEY_DOWN = "\e[{n}B";
    case KEY_LEFT = "\e[{n}D";
    case KEY_RIGHT = "\e[{n}C";
    case KEY_SET_POSITION = "\033[{l};{c}H";
    case KEY_ENTER = "\n";
    case KEY_SPACE = " ";
    case KEY_TAB = "\t";
    case KEY_BACKSPACE = "\x7f";
    case KEY_ESC = "\e";
}
