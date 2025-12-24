<?php

namespace Tuto\Database\Query\Conditions;

use Tuto\Utils\EasyEnum;

enum ConditionType: string
{
    use EasyEnum;

    case WHERE = 'WHERE';
    case AND = 'AND';
    case OR = 'OR';
}