<?php

namespace Tuto\Database\Query\Join;

use Tuto\Collections\Collection;
use Tuto\Database\Query\QueryBuilder;
use Tuto\Database\Query\QueryMaker;

trait ConditionJoinTrait
{
    /** @var Collection<int, JoinQuery> $join */
    private Collection $join;

    /**
     * @return Collection<string, JoinQuery>
     */
    public function getJoin(): Collection
    {
        return $this->join;
    }

    /**
     * @param string|callable(QueryBuilder): void|QueryBuilder $table
     * @param string $alias
     * @param callable(JoinQuery): void $on
     * @return QueryBuilder
     */
    public function innerJoin(string|callable|QueryBuilder $table, string $alias, callable $on): QueryBuilder
    {
        return $this->addJoinQuery(JoinType::INNER, $table, $alias, $on);
    }

    /**
     * @param string|callable(QueryBuilder): void|QueryBuilder $table
     * @param string $alias
     * @param callable(JoinQuery): void $on
     * @return QueryBuilder
     */
    public function leftJoin(string|callable|QueryBuilder $table, string $alias, callable $on): QueryBuilder
    {
        return $this->addJoinQuery(JoinType::LEFT, $table, $alias, $on);
    }

    /**
     * @param string|callable(QueryBuilder): void|QueryBuilder $table
     * @param string $alias
     * @param callable(JoinQuery): void $on
     * @return QueryBuilder
     */
    public function rightJoin(string|callable|QueryBuilder $table, string $alias, callable $on): QueryBuilder
    {
        return $this->addJoinQuery(JoinType::RIGHT, $table, $alias, $on);
    }

    /**
     * @param string|callable(QueryBuilder): void|QueryBuilder $table
     * @param string $alias
     * @param callable(JoinQuery): void $on
     * @return QueryBuilder
     */
    public function fullJoin(string|callable|QueryBuilder $table, string $alias, callable $on): QueryBuilder
    {
        return $this->addJoinQuery(JoinType::FULL, $table, $alias, $on);
    }

    /**
     * @param JoinType $type
     * @param string|callable(QueryBuilder): void|QueryBuilder $table
     * @param string $alias
     * @param callable(JoinQuery): void $on
     * @return QueryBuilder
     */
    private function addJoinQuery(
        JoinType $type,
        string|callable|QueryBuilder $table,
        string $alias,
        callable $on,
    ): QueryBuilder {
        $finalTable = $table;
        if (is_callable($table)) {
            $query = QueryMaker::select();
            $table($query);
            $finalTable = $query;
        }

        $join = new JoinQuery($type, $finalTable, $alias);
        $on($join);

        $this->join->push($join);
        return $this;
    }
}