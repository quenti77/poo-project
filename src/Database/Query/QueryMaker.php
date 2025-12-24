<?php

namespace Tuto\Database\Query;

class QueryMaker
{
    public static function select(): QueryBuilder
    {
        return new QueryBuilder(QueryType::SELECT);
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