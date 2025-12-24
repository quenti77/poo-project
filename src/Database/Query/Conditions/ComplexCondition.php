<?php

namespace Tuto\Database\Query\Conditions;

use InvalidArgumentException;
use Tuto\Collections\Collection;
use Tuto\Database\Query\QueryBuilder;
use Tuto\Database\Query\QueryRender;

class ComplexCondition extends BaseCondition
{
    public function __construct(
        ConditionType $type,
        string $column,
        ConditionOperator $operator,
        array|Collection|QueryBuilder $value,
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
        parent::__construct($type, $column, $operator, $value);
    }

    public function render(): string
    {
        $values = '';
        if ($this->operator->isInOperator()) {
            $values = $this->value
                ->map(fn (int $key, mixed $value) => $this->escapeValue($value))
                ->join(', ');
        }
        if ($this->operator->isExistOperator()) {
            $queryRender = new QueryRender($this->value);
            $values = "({$queryRender->toSql()})";
        }

        return "{$this->type->value} {$this->column} {$this->operator->value} {$values}";
    }

    /**
     * @param mixed $value
     * @return string
     */
    private function escapeValue(mixed $value): string
    {
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        return "'{$value}'";
    }
}