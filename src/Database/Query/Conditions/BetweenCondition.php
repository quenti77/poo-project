<?php

namespace Tuto\Database\Query\Conditions;

class BetweenCondition extends BaseCondition
{
    /**
     * @param ConditionType $type
     * @param string $column
     * @param mixed $start
     * @param mixed $end
     * @param bool $escape
     */
    public function __construct(ConditionType $type, string $column, mixed $start, mixed $end, bool $escape)
    {
        parent::__construct($type, $column, ConditionOperator::BETWEEN, [$start, $end], $escape);
    }

    public function render(): string
    {
        $start = $this->escapeValue($this->value[0]);
        $end = $this->escapeValue($this->value[1]);

        return "{$this->type->value} {$this->column} {$this->operator->value} {$start} AND {$end}";
    }
}
