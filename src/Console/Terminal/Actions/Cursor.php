<?php

namespace Tuto\Console\Terminal\Actions;

enum Cursor: string
{
    case HIDE = "\033[?25l";
    case SHOW = "\033[?25h";
    case SAVE = "\033[s";
    case RESTORE = "\033[u";
    case CLEAR_LINE = "\033[2K";
}
