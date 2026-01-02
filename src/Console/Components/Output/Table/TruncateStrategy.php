<?php

namespace Tuto\Console\Components\Output\Table;

use Tuto\Utils\EasyEnum;

enum TruncateStrategy: string
{
    use EasyEnum;

    case TRUNCATE = 'truncate';
    case WRAP = 'wrap';
    case NO_LIMIT = 'no_limit';
}
