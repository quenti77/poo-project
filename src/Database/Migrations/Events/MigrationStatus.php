<?php

namespace Tuto\Database\Migrations\Events;

use Tuto\Utils\EasyEnum;

enum MigrationStatus: string
{
    use EasyEnum;

    case PROCESSING = 'processing';
    case FINISHED = 'finished';
    case ERROR = 'error';
}
