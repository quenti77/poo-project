<?php

namespace Tuto\Database\Query\Join;

use Tuto\Utils\EasyEnum;

enum JoinType: string
{
    use EasyEnum;

    case INNER = 'INNER JOIN';
    case LEFT = 'LEFT JOIN';
    case RIGHT = 'RIGHT JOIN';
    case FULL = 'FULL JOIN';
}
