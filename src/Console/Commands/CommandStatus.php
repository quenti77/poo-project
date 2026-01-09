<?php

namespace Tuto\Console\Commands;

use Tuto\Utils\EasyEnum;

enum CommandStatus: int
{
    use EasyEnum;

    case SUCCESS = 0;
    case GENERIC_FAILURE = 1;
}
