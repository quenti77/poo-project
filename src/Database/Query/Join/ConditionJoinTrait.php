<?php

namespace Tuto\Database\Query\Join;

use Tuto\Collections\Collection;
use Tuto\Database\Query\QueryBuilder;

trait ConditionJoinTrait
{
    /** @var Collection<int, JoinQuery> $join */
    private Collection $join;

    /**
     * @param string|callable(QueryBuilder): void $table
     * @param string $alias
     * @return JoinQuery
     */
    public function innerJoin(string|callable $table, string $alias): JoinQuery
    {
        return new JoinQuery(JoinType::INNER, $table, $alias);
    }

    /**
     * @param string|callable(QueryBuilder): void $table
     * @param string $alias
     * @return JoinQuery
     */
    public function leftJoin(string|callable $table, string $alias): JoinQuery
    {
        return new JoinQuery(JoinType::LEFT, $table, $alias);
    }

    /**
     * @param string|callable(QueryBuilder): void $table
     * @param string $alias
     * @return JoinQuery
     */
    public function rightJoin(string|callable $table, string $alias): JoinQuery
    {
        return new JoinQuery(JoinType::RIGHT, $table, $alias);
    }

    /**
     * @param string|callable(QueryBuilder): void $table
     * @param string $alias
     * @return JoinQuery
     */
    public function fullJoin(string|callable $table, string $alias): JoinQuery
    {
        return new JoinQuery(JoinType::FULL, $table, $alias);
    }
}