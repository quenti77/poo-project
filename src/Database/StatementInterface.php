<?php

namespace Tuto\Database;

interface StatementInterface
{
    public function bind(string $name, mixed $value): bool;

    public function execute(): bool;

    public function fetch(): array|false;

    public function fetchAll(): array;
}