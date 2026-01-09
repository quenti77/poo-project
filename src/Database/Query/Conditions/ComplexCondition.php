<?php

namespace Tuto\Database\Query\Conditions;

use InvalidArgumentException;
use Tuto\Collections\Collection;
use Tuto\Database\Query\QueryBuilder;
use Tuto\Database\Query\QueryRender;

class ComplexCondition extends BaseCondition
{
    /**
     * @param ConditionType $type
     * @param string $column
     * @param ConditionOperator $operator
     * @param array|Collection|QueryBuilder $value
     * @param bool $escape
     */
    public function __construct(
        ConditionType $type,
        string $column,
        ConditionOperator $operator,
        array|Collection|QueryBuilder $value,
        bool $escape
    ) {
        if ($value instanceof QueryBuilder && $operator->isInOperator()) {
            throw new InvalidArgumentException("Operator IN, NOT IN needs an array or Collection");
        }
        if (!($value instanceof QueryBuilder) && $operator->isExistOperator()) {
            throw new InvalidArgumentException("Operator EXIST, NOT EXIST needs a subquery");
        }

        if (is_array($value) && $operator->isInOperator()) {
            $value = collect($value);
        }
        parent::__construct($type, $column, $operator, $value, $escape);
    }

    public function render(): string
    {
        $values = '';
        if ($this->operator->isInOperator()) {
            $values = $this->value
                ->map(fn (int $key, mixed $value) => $this->escapeValue($value))
                ->join(', ');
            $values = "({$values})";
        }
        if ($this->operator->isExistOperator()) {
            $queryRender = new QueryRender($this->value);
            $values = "({$queryRender->toSql()})";
        }

        return "{$this->type->value} {$this->column} {$this->operator->value} {$values}";
    }
}
