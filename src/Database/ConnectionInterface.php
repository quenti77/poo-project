<?php

namespace Tuto\Database;

interface ConnectionInterface
{
    public function request(string $statement, array $parameters = []): StatementInterface;

    public function startTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;

}