<?php

namespace Tuto\Database\Pdo;

use PDO;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\StatementInterface;

class PdoConnection extends PDO implements ConnectionInterface
{
    public function __construct(
        string $type,
        string $host,
        int $port,
        string $name,
        string $user,
        #[\SensitiveParameter]
        string $pass,
    ) {
        $dsn = "{$type}:host={$host};dbname={$name};port={$port}";
        parent::__construct($dsn, $user, $pass);

        $this->setAttribute(parent::ATTR_DEFAULT_FETCH_MODE, parent::FETCH_ASSOC);
        $this->setAttribute(parent::ATTR_EMULATE_PREPARES, false);
    }

    public function request(string $statement, array $parameters = []): StatementInterface
    {
        $request = new PdoStatement($this->prepare($statement));

        foreach ($parameters as $fieldName => $value) {
            $request->bind($fieldName, $value);
        }

        $request->execute();
        return $request;
    }
}