<?php

namespace Tuto\Database\Query;

use Tuto\Collections\Collection;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\Query\Conditions\BaseCondition;
use Tuto\Database\Query\Join\JoinQuery;
use Tuto\Database\StatementInterface;

class QueryRender
{
    public function __construct(private readonly QueryBuilder $queryBuilder)
    {
    }

    /**
     * @return Collection<string, mixed>
     */
    public function getParameters(): Collection
    {
        return $this->queryBuilder->getParameters();
    }

    /**
     * @return string
     */
    public function toSql(): string
    {
        $queryType = $this->queryBuilder->getType();
        $parts = collect([$queryType->value]);

        if ($this->queryBuilder->getFrom()->isEmpty()) {
            throw new InvalidQuerySyntaxException("Query must be defined a table");
        }

        $parts = match ($queryType) {
            QueryType::SELECT => $this->selectRender($parts),
            QueryType::INSERT => $this->insertRender($parts),
            QueryType::UPDATE => $this->updateRender($parts),
            QueryType::DELETE => $this->deleteRender($parts),
            default => throw new InvalidQuerySyntaxException('Unknown Query Type'),
        };

        return $parts->join(' ');
    }

    /**
     * @param ConnectionInterface $connection
     * @return StatementInterface
     */
    public function makeRequest(ConnectionInterface $connection): StatementInterface
    {
        return $connection->request($this->toSql(), $this->getParameters()->all());
    }

    /**
     * @param Collection<int, string> $parts
     * @return Collection<int, string>
     */
    private function selectRender(Collection $parts): Collection
    {
        $fields = $this->queryBuilder->getFields();
        $parts->push($fields->isEmpty() ? '*' : $this->renderTableWithAlias($fields));

        $from = $this->queryBuilder->getFrom()->values()->first();
        $parts->push('FROM');
        $parts->push($this->renderTableWithAlias($this->queryBuilder->getFrom()));

        $join = $this->queryBuilder->getJoin();
        if (!$join->isEmpty()) {
            $this->renderJoin($parts, $join);
        }

        /** @var Collection<int, BaseCondition> $where */
        $where = $this->queryBuilder->getWhere();
        if (!$where->isEmpty()) {
            $parts->push($this->renderCondition($where));
        }

        /** @var Collection<int, string> $groupBy */
        $groupBy = $this->queryBuilder->getGroupBy();
        if (!$groupBy->isEmpty()) {
            $parts->push('GROUP BY');
            $parts->push($groupBy->join(', '));
        }

        /** @var Collection<string, QueryOrder> $orderBy */
        $orderBy = $this->queryBuilder->getOrderBy();
        if (!$orderBy->isEmpty()) {
            $parts->push('ORDER BY');

            $orderByParts = $orderBy->map(static fn (string $column, QueryOrder $order) => "{$column} {$order->value}");
            $parts->push($orderByParts->join(', '));
        }

        $this->renderLimit($parts);

        return $parts;
    }

    /**
     * @param Collection<int, string> $parts
     * @return Collection<int, string>
     */
    private function insertRender(Collection $parts): Collection
    {
        $from = $this->queryBuilder->getFrom()->values()->first();
        $parts->push($from);

        $values = $this->queryBuilder->getValues();
        if ($values->isEmpty()) {
            throw new InvalidQuerySyntaxException("INSERT must be used with one or more values");
        }

        $parts->push("({$values->keys()->join(', ')})");
        $parts->push('VALUES');
        $parts->push("({$values->values()->join(', ')})");

        return $parts;
    }

    /**
     * @param Collection<int, string> $parts
     * @return Collection<int, string>
     */
    private function updateRender(Collection $parts): Collection
    {
        $parts->push($this->renderTableWithAlias($this->queryBuilder->getFrom()));

        $values = $this->queryBuilder->getValues();
        if ($values->isEmpty()) {
            throw new InvalidQuerySyntaxException("UPDATE must be used with one or more values");
        }

        $parts->push('SET');
        $setParts = $values->map(static fn (string $key, mixed $value) => "{$key} = {$value}");
        $parts->push($setParts->join(', '));

        /** @var Collection<int, BaseCondition> $where */
        $where = $this->queryBuilder->getWhere();
        if (!$where->isEmpty()) {
            $parts->push($this->renderCondition($where));
        }

        return $parts;
    }

    /**
     * @param Collection<int, string> $parts
     * @return Collection<int, string>
     */
    private function deleteRender(Collection $parts): Collection
    {
        $from = $this->queryBuilder->getFrom()->values()->first();
        $parts->push($from);

        /** @var Collection<int, BaseCondition> $where */
        $where = $this->queryBuilder->getWhere();
        if (!$where->isEmpty()) {
            $parts->push($this->renderCondition($where));
        }

        $this->renderLimit($parts);

        return $parts;
    }

    /**
     * @param Collection<int|string, string|QueryBuilder> $tables
     * @return string
     */
    private function renderTableWithAlias(Collection $tables): string
    {
        $render = collect();

        foreach ($tables as $alias => $table) {
            if ($table instanceof QueryBuilder) {
                $currentRender = $table->render();
                $render->push($this->renderAlias($alias, "({$currentRender->toSql()})"));
            } else {
                $render->push($this->renderAlias($alias, $table));
            }
        }

        return $render->join(', ');
    }

    /**
     * @param Collection<int, string> $parts
     * @param Collection<int, JoinQuery> $joins
     * @return void
     */
    private function renderJoin(Collection $parts, Collection $joins): void
    {
        foreach ($joins as $join) {
            $joinRender = $join->getType()->value;
            $parts->push($joinRender);

            $table = collect([$join->getAlias() => $join->getTable()]);
            $tableRender = $this->renderTableWithAlias($table);
            $parts->push($tableRender);

            $conditionRender = $this->renderCondition($join->getWhere());
            $parts->push($conditionRender);
        }
    }

    /**
     * @param int|string $alias
     * @param mixed $value
     * @return string
     */
    private function renderAlias(int|string $alias, mixed $value): string
    {
        return is_int($alias) ? $value : "{$value} AS {$alias}";
    }

    /**
     * @param Collection<int, BaseCondition> $conditions
     * @return string
     */
    private function renderCondition(Collection $conditions): string
    {
        return $conditions
            ->map(static fn (int $key, BaseCondition $condition) => $condition->render())
            ->join(' ');
    }

    /**
     * @param Collection $parts
     * @return void
     */
    private function renderLimit(Collection $parts): void
    {
        $limit = $this->queryBuilder->getLimit();
        $offset = $this->queryBuilder->getOffset();

        if ($limit !== null) {
            $parts->push("LIMIT {$limit}");

            if ($offset !== null) {
                $parts->push("OFFSET {$offset}");
            }
        }
    }
}
