<?php

namespace Tuto\Template\Tokens;

enum Token: string
{
    case TEXT = 'text';
    case VAR_START = 'var_start';
    case VAR_END = 'var_end';
    case BLOCK_START = 'block_start';
    case BLOCK_END = 'block_end';
    case COMMENT = 'comment';
}
