<?php

namespace Tuto\Database;

interface ConnectionInterface
{
    /**
     * @param string|StatementInterface $statement
     * @param array<string, mixed> $parameters
     * @return StatementInterface
     */
    public function request(string|StatementInterface $statement, array $parameters = []): StatementInterface;

    public function startTransaction(): bool;

    public function commit(): bool;

    public function rollback(): bool;
}