<?php

namespace Tuto\Database\Query;

use Tuto\Collections\Collection;
use Tuto\Database\Query\Conditions\BaseCondition;

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
     * @param Collection<int, string> $parts
     * @return Collection<int, string>
     */
    private function selectRender(Collection $parts): Collection
    {
        $from = $this->queryBuilder->getFrom()->values()[0];
        $parts->push($from);

        return $parts;
    }

    /**
     * @param Collection<int, string> $parts
     * @return Collection<int, string>
     */
    private function insertRender(Collection $parts): Collection
    {
        $from = $this->queryBuilder->getFrom()->values()[0];
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
        $from = $this->queryBuilder->getFrom()->values()[0];
        $parts->push($from);

        return $parts;
    }

    /**
     * @param Collection $tables
     * @return string
     */
    private function renderTableWithAlias(Collection $tables): string
    {
        $render = collect();

        foreach ($tables as $alias => $table) {
            if ($table instanceof QueryBuilder) {
                $currentRender = $table->render();
                $render->push($this->renderAlias($alias, "({$currentRender->toSql()})"));
                $this->queryBuilder->getParameters()->merge($currentRender->getParameters());
            } else {
                $render->push($this->renderAlias($alias, $table));
            }
        }

        return $render->join(', ');
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
}