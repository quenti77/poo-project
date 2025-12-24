<?php

namespace Tuto\Database\Query\Conditions;

use InvalidArgumentException;

class SimpleCondition extends BaseCondition
{
    /**
     * @param ConditionType $type
     * @param string $column
     * @param ConditionOperator $operator
     * @param mixed $value
     */
    public function __construct(ConditionType $type, string $column, ConditionOperator $operator, mixed $value)
    {
        if (!$operator->isSimpleOperator()) {
            throw new InvalidArgumentException("This condition use simple operator");
        }
        parent::__construct($type, $column, $operator, $value);
    }

    public function render(): string
    {
        return "{$this->type->value} {$this->column} {$this->operator->value} {$this->value}";
    }
}