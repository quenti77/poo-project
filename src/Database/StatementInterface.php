<?php

namespace Tuto\Database;

interface StatementInterface
{
    /**
     * @param string $name
     * @param mixed $value
     * @return bool
     */
    public function bind(string $name, mixed $value): bool;

    public function execute(): bool;

    /**
     * @return array<string, mixed>|false
     */
    public function fetch(): array|false;

    /**
     * @return array<array<string, mixed>>
     */
    public function fetchAll(): array;
}