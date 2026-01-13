<?php

namespace Tuto\Queue\Events;

use Tuto\Utils\EasyEnum;

enum JobStatus: string
{
    use EasyEnum;

    case PROCESSING = 'processing';
    case FINISHED = 'finished';
    case ERROR = 'error';
}
