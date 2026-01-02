<?php

namespace Tuto\Console\Components\Output\Table;

use Tuto\Utils\EasyEnum;

enum Alignment: string
{
    use EasyEnum;

    case LEFT = 'left';
    case CENTER = 'center';
    case RIGHT = 'right';
}
