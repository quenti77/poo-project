<?php

namespace Tuto\Database;

interface ConnectionInterface
{
    public function request(string $statement, array $parameters = []): StatementInterface;
}