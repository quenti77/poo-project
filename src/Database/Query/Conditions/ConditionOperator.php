<?php

namespace Tuto\Database\Query\Conditions;

use Tuto\Utils\EasyEnum;

enum ConditionOperator: string
{
    use EasyEnum;

    case EQ = '=';
    case NOT_EQ = '<>';
    case GT = '>';
    case GT_OR_EQ = '>=';
    case LT = '<';
    case LT_OR_EQ = '<=';

    case LIKE = 'LIKE';
    case NOT_LIKE = 'NOT LIKE';
    case BETWEEN = 'BETWEEN';
    case IN = 'IN';
    case NOT_IN = 'NOT IN';
    case EXISTS = 'EXISTS';
    case NOT_EXISTS = 'NOT EXISTS';

    public function isSimpleOperator(): bool
    {
        return in_array($this, [
            self::EQ,
            self::NOT_EQ,
            self::GT,
            self::GT_OR_EQ,
            self::LT,
            self::LT_OR_EQ,
            self::LIKE,
            self::NOT_LIKE,
        ]);
    }

    public function isInOperator(): bool
    {
        return in_array($this, [self::IN, self::NOT_IN], true);
    }

    public function isExistOperator(): bool
    {
        return in_array($this, [self::EXISTS, self::NOT_EXISTS], true);
    }
}
