<?php

namespace Tuto\Template\Tokens;

use Tuto\Utils\EasyEnum;

enum UnaryOperator: string
{
    use EasyEnum;

    case MINUS = '-';
    case NOT = 'not';
}
