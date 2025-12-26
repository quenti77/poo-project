<?php

namespace Tuto\Database\Query;

class QueryMaker
{
    /**
     * @param string|array ...$fields
     * @return QueryBuilder
     */
    public static function select(string|array ...$fields): QueryBuilder
    {
        return new QueryBuilder(QueryType::SELECT)->select(...$fields);
    }

    public static function insert(): QueryBuilder
    {
        return new QueryBuilder(QueryType::INSERT);
    }

    public static function update(): QueryBuilder
    {
        return new QueryBuilder(QueryType::UPDATE);
    }

    public static function delete(): QueryBuilder
    {
        return new QueryBuilder(QueryType::DELETE);
    }
}