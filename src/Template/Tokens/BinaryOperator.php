<?php

namespace Tuto\Template\Tokens;

use Tuto\Utils\EasyEnum;

enum BinaryOperator: string
{
    use EasyEnum;

    case PLUS = '+';
    case MINUS = '-';
    case MULTIPLY = '*';
    case DIVIDE = '/';
    case MODULUS = '%';

    case EQUALITY = '==';
    case NOT_EQUALITY = '!=';
    case GREATER_THAN = '>';
    case GREATER_THAN_EQUAL = '>=';
    case LESS_THAN = '<';
    case LESS_THAN_EQUAL = '<=';
}
