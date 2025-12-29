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

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public static function insert(string $table): QueryBuilder
    {
        return new QueryBuilder(QueryType::INSERT)->from($table);
    }

    /**
     * @param string ...$tables
     * @return QueryBuilder
     */
    public static function update(string ...$tables): QueryBuilder
    {
        $query = new QueryBuilder(QueryType::UPDATE);
        foreach ($tables as $table) {
            $query->from($table);
        }
        return $query;
    }

    /**
     * @param string $table
     * @return QueryBuilder
     */
    public static function delete(string $table): QueryBuilder
    {
        return new QueryBuilder(QueryType::DELETE)->from($table);
    }
}