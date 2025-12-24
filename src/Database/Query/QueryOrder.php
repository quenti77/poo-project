<?php

namespace Tuto\Database\Query;

use Tuto\Utils\EasyEnum;

enum QueryOrder: string
{
    use EasyEnum;

    case ASC = 'ASC';
    case DESC = 'DESC';
}