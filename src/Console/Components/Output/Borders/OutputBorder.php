<?php

namespace Tuto\Console\Components\Output\Borders;

use Tuto\Utils\EasyEnum;

enum OutputBorder: string
{
    use EasyEnum;

    case NONE = 'none';
    case ASCII = 'ascii';
    case SIMPLE = 'simple';
    case DOUBLE = 'double';

    /**
     * @return BorderInterface
     */
    public function getBorder(): BorderInterface
    {
        return match($this) {
            self::NONE => new NoneBorder(),
            self::ASCII => new AsciiBorder(),
            self::SIMPLE => new SimpleBorder(),
            self::DOUBLE => new DoubleBorder(),
        };
    }
}
