<?php

namespace Tuto\Database\Query\Conditions;

use InvalidArgumentException;

class BetweenCondition extends BaseCondition
{
    /**
     * @param ConditionType $type
     * @param string $column
     * @param array $value
     */
    public function __construct(ConditionType $type, string $column, mixed $start, mixed $end)
    {
        parent::__construct($type, $column, ConditionOperator::BETWEEN, [$start, $end]);
    }

    public function render(): string
    {
        return "{$this->type->value} {$this->column} {$this->operator->value} {$this->value[0]} AND {$this->value[1]}";
    }
}