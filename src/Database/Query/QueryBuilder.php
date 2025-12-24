<?php

namespace Tuto\Database\Query;

use Tuto\Collections\Collection;
use Tuto\Database\Query\Conditions\ConditionWhereTrait;

class QueryBuilder
{
    use ConditionWhereTrait;

    private QueryType $type;

    /** @var Collection<int|string, string|QueryBuilder> $from */
    private Collection $from;

    /** @var Collection<int|string, string> $fields */
    private Collection $fields;

    /** @var Collection<int, mixed> $values */
    private Collection $values;

    /** @var Collection<string, > $join */
    private Collection $join;

    public function __construct(QueryType $type)
    {
        $this->type = $type;
        $this->from = collect();
        $this->fields = collect();
        $this->values = collect();
        $this->parameters = collect();
        $this->join = collect();
        $this->where = collect();
    }

    /**
     * @return QueryType
     */
    public function getType(): QueryType
    {
        return $this->type;
    }

    /**
     * @return Collection<int|string, string|QueryBuilder>
     */
    public function getFrom(): Collection
    {
        return $this->from;
    }

    /**
     * @return Collection<int|string, string>
     */
    public function getFields(): Collection
    {
        return $this->fields;
    }

    /**
     * @return Collection<int, mixed>
     */
    public function getValues(): Collection
    {
        return $this->values;
    }

    /**
     * @return Collection<string, >
     */
    public function getJoin(): Collection
    {
        return $this->join;
    }

    /**
     * @param string|callable(QueryBuilder): void $from
     * @param string|null $alias
     * @return self
     */
    public function from(string|callable $from, string|null $alias = null): self
    {
        if (!$this->from->isEmpty() && !$this->type->canUseMultipleFrom()) {
            throw new InvalidQuerySyntaxException("Multiple FROM only can be used in SELECT or UPDATE request");
        }

        if (is_string($from)) {
            $alias === null ? $this->from->push($from) : $this->from[$alias] = $from;
            return $this;
        }

        if (!$this->type->canUseSubQuery()) {
            throw new InvalidQuerySyntaxException("The UPDATE can not access sub query into FROM");
        }

        $subQuery = QueryMaker::select();
        $from($subQuery);
        $alias === null ? $this->from->push($subQuery) : $this->from[$alias] = $subQuery;

        return $this;
    }

    /**
     * @param string|array ...$fields
     * @return self
     */
    public function select(string|array ...$fields): self
    {
        if ($this->type !== QueryType::SELECT) {
            throw new InvalidQuerySyntaxException("Only select can be used in SELECT query");
        }

        foreach ($fields as $field) {
            if (is_string($field)) {
                $field = [$field];
            }
            $this->appendFields($field);
        }

        return $this;
    }

    /**
     * @param array<string, string> $values
     * @param bool $escape
     * @return $this
     */
    public function values(array $values, bool $escape = true): self
    {
        foreach ($values as $column => $value) {
            $this->value($column, $value, $escape);
        }

        return $this;
    }

    /**
     * @param string $column
     * @param mixed $value
     * @param bool $escape
     * @return void
     */
    public function value(string $column, mixed $value, bool $escape = true): void
    {
        if (!$this->type->canUseValues()) {
            throw new InvalidQuerySyntaxException("Can not set values outside INSERT or UPDATE queries");
        }

        if ($escape) {
            $value = $this->escapeValue($column, $value);
        }
        $this->values[$column] = $value;
    }

    /**
     * @param array<int|string, string> $fields
     * @return void
     */
    private function appendFields(array $fields): void
    {
        foreach ($fields as $alias => $column) {
            is_int($alias) ? $this->fields->push($column) : $this->fields[$alias] = $column;
        }
    }
}