<?php

namespace Tuto\Database\Query\Join;

use Tuto\Database\Query\Conditions\ConditionType;
use Tuto\Database\Query\Conditions\ConditionWhereTrait;
use Tuto\Database\Query\QueryBuilder;

class JoinQuery
{
    use ConditionWhereTrait;

    /**
     * @param JoinType $type
     * @param string|QueryBuilder $table
     * @param string $alias
     */
    public function __construct(
        private readonly JoinType $type,
        private readonly string|QueryBuilder $table,
        private readonly string $alias,
    ) {
        $this->where = collect();
        $this->parameters = collect();
        $this->defaultConditionType = ConditionType::ON;
    }

    /**
     * @return JoinType
     */
    public function getType(): JoinType
    {
        return $this->type;
    }

    /**
     * @return string|QueryBuilder
     */
    public function getTable(): string|QueryBuilder
    {
        return $this->table;
    }

    /**
     * @return string
     */
    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @param string $column
     * @param mixed $op
     * @param mixed|null $value
     * @param bool $escape
     * @return self
     */
    public function on(string $column, mixed $op, mixed $value = null, bool $escape = true): self
    {
        return $this->addWhere(ConditionType::AND, $column, $op, $value, $escape);
    }
}