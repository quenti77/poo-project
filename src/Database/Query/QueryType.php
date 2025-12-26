<?php

namespace Tuto\Database\Query;

use Tuto\Utils\EasyEnum;

enum QueryType: string
{
    use EasyEnum;

    case NONE = '';
    case SELECT = 'SELECT';
    case INSERT = 'INSERT INTO';
    case UPDATE = 'UPDATE';
    case DELETE = 'DELETE FROM';

    public function canUseMultipleFrom(): bool
    {
        return in_array($this, [self::SELECT, self::UPDATE], true);
    }

    public function canUseValues(): bool
    {
        return in_array($this, [self::INSERT, self::UPDATE], true);
    }

    public function canUseLimit(): bool
    {
        return in_array($this, [self::SELECT, self::DELETE], true);
    }

    public function canUseSubQuery(): bool
    {
        return $this === self::SELECT;
    }
}
