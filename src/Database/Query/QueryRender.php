<?php

namespace Tuto\Database\Query;

use Tuto\Collections\Collection;

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
        return 'select 1';
    }
}