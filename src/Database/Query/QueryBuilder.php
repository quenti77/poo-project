<?php

namespace Tuto\Database\Query;

use Tuto\Collections\Collection;
use Tuto\Database\Query\Conditions\ConditionType;
use Tuto\Database\Query\Conditions\ConditionWhereTrait;
use Tuto\Database\Query\Join\ConditionJoinTrait;

class QueryBuilder
{
    use ConditionJoinTrait;
    use ConditionWhereTrait;

    private QueryType $type;

    /** @var Collection<int|string, string|QueryBuilder> $from */
    private Collection $from;

    /** @var Collection<int|string, string> $fields */
    private Collection $fields;

    /** @var Collection<int, mixed> $values */
    private Collection $values;

    /** @var Collection<int, string> */
    private Collection $groupBy;

    /** @var Collection<string, QueryOrder> $orderBy */
    private Collection $orderBy;

    /** @var string|null $offset */
    private string|null $offset = null;

    /** @var string|null $limit */
    private string|null $limit = null;

    public function __construct(QueryType $type)
    {
        $this->type = $type;
        $this->from = collect();
        $this->fields = collect();
        $this->values = collect();
        $this->parameters = collect();
        $this->join = collect();
        $this->where = collect();
        $this->groupBy = collect();
        $this->orderBy = collect();

        $this->defaultConditionType = ConditionType::WHERE;
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
     * @return Collection<int, string>
     */
    public function getGroupBy(): Collection
    {
        return $this->groupBy;
    }

    /**
     * @return Collection<string, QueryOrder>
     */
    public function getOrderBy(): Collection
    {
        return $this->orderBy;
    }

    /**
     * @return string|null
     */
    public function getOffset(): ?string
    {
        return $this->offset;
    }

    /**
     * @return string|null
     */
    public function getLimit(): ?string
    {
        return $this->limit;
    }

    /**
     * @param string|callable(QueryBuilder): void|self $from
     * @param string|null $alias
     * @return self
     */
    public function from(string|callable|self $from, string|null $alias = null): self
    {
        if (!$this->from->isEmpty() && !$this->type->canUseMultipleFrom()) {
            throw new InvalidQuerySyntaxException("Multiple FROM only can be used in SELECT or UPDATE request");
        }

        if (is_string($from)) {
            $alias === null ? $this->from->push($from) : $this->from[$alias] = $from;
            return $this;
        }

        if (!$this->type->canUseSubQuery()) {
            throw new InvalidQuerySyntaxException("Only SELECT can access sub query into FROM");
        }

        $subQuery = $from;
        if (is_callable($from)) {
            $subQuery = QueryMaker::select();
            $from($subQuery);
        }

        $alias === null ? $this->from->push($subQuery) : $this->from[$alias] = $subQuery;

        $this->parameters = $this->parameters->merge($subQuery->getParameters());
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
     * @param array<string, mixed> $values
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
     * @return self
     */
    public function value(string $column, mixed $value, bool $escape = true): self
    {
        if (!$this->type->canUseValues()) {
            throw new InvalidQuerySyntaxException("Can not set values outside INSERT or UPDATE queries");
        }

        if ($escape) {
            $value = $this->escapeValue($column, $value);
        }
        $this->values[$column] = $value;
        return $this;
    }

    /**
     * @param string ...$columns
     * @return $this
     */
    public function groupBy(string ...$columns): self
    {
        if ($this->type !== QueryType::SELECT) {
            throw new InvalidQuerySyntaxException("Can not used 'group by' outside SELECT queries");
        }

        $this->groupBy->push(...$columns);

        return $this;
    }

    /**
     * @param string $column
     * @param string|QueryOrder $order
     * @return self
     */
    public function orderBy(string $column, string|QueryOrder $order = QueryOrder::ASC): self
    {
        if ($this->type !== QueryType::SELECT) {
            throw new InvalidQuerySyntaxException("Can not used 'order by' outside SELECT queries");
        }

        if (is_string($order)) {
            $order = QueryOrder::tryFrom($order) ?? QueryOrder::ASC;
        }

        $this->orderBy[$column] = $order;
        return $this;
    }

    /**
     * @param string $column
     * @return $this
     */
    public function orderByDesc(string $column): self
    {
        return $this->orderBy($column, QueryOrder::DESC);
    }

    /**
     * @param int|string $offset
     * @return self
     */
    public function offset(int|string $offset): self
    {
        if (!$this->type->canUseLimit()) {
            throw new InvalidQuerySyntaxException("Can not used 'offset' outside SELECT or DELETE queries");
        }
        $this->offset = is_int($offset) ? $this->escapeValue('offset', $offset) : $offset;
        return $this;
    }

    /**
     * @param int|string $limit
     * @return self
     */
    public function limit(int|string $limit): self
    {
        if (!$this->type->canUseLimit()) {
            throw new InvalidQuerySyntaxException("Can not used 'limit' outside SELECT or DELETE queries");
        }
        $this->limit = is_int($limit) ? $this->escapeValue('limit', $limit) : $limit;
        return $this;
    }

    /**
     * @return QueryRender
     */
    public function render(): QueryRender
    {
        return new QueryRender($this);
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
