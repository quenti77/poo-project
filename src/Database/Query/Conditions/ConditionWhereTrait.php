<?php

namespace Tuto\Database\Query\Conditions;

use Tuto\Collections\Collection;
use Tuto\Database\Query\InvalidQuerySyntaxException;
use Tuto\Database\Query\QueryBuilder;
use Tuto\Database\Query\QueryMaker;

trait ConditionWhereTrait
{
    /** @var Collection<string, mixed> $parameters */
    private Collection $parameters;

    /** @var Collection<int, BaseCondition> $where */
    private Collection $where;

    /**
     * @return Collection<string, mixed>
     */
    public function getParameters(): Collection
    {
        return $this->parameters;
    }

    /**
     * @return Collection<int, BaseCondition>
     */
    public function getWhere(): Collection
    {
        return $this->where;
    }

    /**
     * @param string $column
     * @param mixed $op
     * @param mixed|null $value
     * @param bool $escape
     * @return self
     */
    public function where(string $column, mixed $op, mixed $value = null, bool $escape = true): self
    {
        return $this->addWhere(ConditionType::AND, $column, $op, $value, $escape);
    }

    /**
     * @param string $column
     * @param mixed $op
     * @param mixed|null $value
     * @param bool $escape
     * @return self
     */
    public function orWhere(string $column, mixed $op, mixed $value = null, bool $escape = true): self
    {
        return $this->addWhere(ConditionType::OR, $column, $op, $value, $escape);
    }

    /**
     * @param string $column
     * @param mixed $start
     * @param mixed $end
     * @return self
     */
    public function whereBetween(string $column, mixed $start, mixed $end): self
    {
        return $this->addWhere(ConditionType::AND, $column, 'BETWEEN', [$start, $end]);
    }

    /**
     * @param string $column
     * @param mixed $start
     * @param mixed $end
     * @return self
     */
    public function orWhereBetween(string $column, mixed $start, mixed $end): self
    {
        return $this->addWhere(ConditionType::OR, $column, 'BETWEEN', [$start, $end]);
    }

    /**
     * @param string $column
     * @param array|Collection $values
     * @return self
     */
    public function whereIn(string $column, array|Collection $values): self
    {
        return $this->addWhere(ConditionType::AND, $column, 'IN', $values);
    }

    /**
     * @param string $column
     * @param array|Collection $values
     * @return self
     */
    public function orWhereIn(string $column, array|Collection $values): self
    {
        return $this->addWhere(ConditionType::OR, $column, 'IN', $values);
    }

    /**
     * @param string $column
     * @param array|Collection $values
     * @return self
     */
    public function whereNotIn(string $column, array|Collection $values): self
    {
        return $this->addWhere(ConditionType::AND, $column, 'NOT IN', $values);
    }

    /**
     * @param string $column
     * @param array|Collection $values
     * @return self
     */
    public function orWhereNotIn(string $column, array|Collection $values): self
    {
        return $this->addWhere(ConditionType::OR, $column, 'NOT IN', $values);
    }

    /**
     * @param string $column
     * @param callable(QueryBuilder): void $values
     * @return self
     */
    public function whereExists(string $column, callable $values): self
    {
        return $this->addWhere(ConditionType::AND, $column, 'EXISTS', $values);
    }

    /**
     * @param string $column
     * @param callable(QueryBuilder): void $values
     * @return self
     */
    public function orWhereExists(string $column, callable $values): self
    {
        return $this->addWhere(ConditionType::OR, $column, 'EXISTS', $values);
    }

    /**
     * @param string $column
     * @param callable(QueryBuilder): void $values
     * @return self
     */
    public function whereNotExists(string $column, callable $values): self
    {
        return $this->addWhere(ConditionType::AND, $column, 'NOT EXISTS', $values);
    }

    /**
     * @param string $column
     * @param callable(QueryBuilder): void $values
     * @return self
     */
    public function orWhereNotExists(string $column, callable $values): self
    {
        return $this->addWhere(ConditionType::OR, $column, 'NOT EXISTS', $values);
    }

    /**
     * @param ConditionType $type
     * @param string $column
     * @param string $op
     * @param mixed|null $value
     * @param bool $escape
     * @return self
     */
    private function addWhere(
        ConditionType $type,
        string $column,
        mixed $op,
        mixed $value = null,
        bool $escape = true,
    ): self {
        $conditionType = $this->where->isEmpty() ? ConditionType::WHERE : $type;
        $operator = ConditionOperator::tryFrom($op);
        if ($operator === null && $value === null) {
            $value = $operator;
            $operator = ConditionOperator::EQ;
        }

        if ($operator->isSimpleOperator()) {
            if ($escape) {
                $value = $this->escapeValue($column, $value);
            }
            $this->where->push(new SimpleCondition($conditionType, $column, $operator, $value));
            return $this;
        }

        if ($operator === ConditionOperator::BETWEEN) {
            if (!is_countable($value) || count($value) !== 2) {
                throw new InvalidQuerySyntaxException("To use BETWEEN, we need an array with 2 values");
            }
            [$start, $end] = $value;
            if ($escape) {
                $start = $this->escapeValue($column . '_start', $start);
                $end = $this->escapeValue($column . '_end', $end);
            }
            $this->where->push(new BetweenCondition($conditionType, $column, $start, $end));
            return $this;
        }

        if ($operator->isInOperator()) {
            if (!is_array($value) && !($value instanceof Collection)) {
                throw new InvalidQuerySyntaxException("To use IN / NOT IN, we need an array or collection");
            }
            $this->where->push(new ComplexCondition($conditionType, $column, $operator, $value));
        }

        if ($operator->isExistOperator()) {
            if (!is_callable($value)) {
                throw new InvalidQuerySyntaxException("To use EXISTS / NOT EXISTS, we need a callable");
            }
            $query = QueryMaker::select();
            $value($query);

            $this->where->push(new ComplexCondition($conditionType, $column, $operator, $query));
            return $this;
        }

        throw new InvalidQuerySyntaxException("Operator not exist : {$operator->value}");
    }

    /**
     * @param string $column
     * @param mixed $value
     * @return string
     */
    private function escapeValue(string $column, mixed $value): string
    {
        $parameterName = ":{$column}";

        $this->parameters[$parameterName] = $value;
        return $parameterName;
    }
}