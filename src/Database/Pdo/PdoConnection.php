<?php

namespace Tuto\Database\Pdo;

use PDO;
use PDOException;
use RuntimeException;
use SensitiveParameter;
use Throwable;
use Tuto\Database\ConnectionInterface;
use Tuto\Database\Exceptions\SqlStatementException;
use Tuto\Database\StatementInterface;

class PdoConnection extends PDO implements ConnectionInterface
{
    private bool $isTransaction = false;

    public function __construct(
        string $type,
        string $host,
        int $port,
        string $username,
        #[SensitiveParameter]
        string $password,
        string $name,
    ) {
        $dsn = "{$type}:host={$host};dbname={$name};port={$port}";
        parent::__construct($dsn, $username, $password);

        $this->setAttribute(parent::ATTR_DEFAULT_FETCH_MODE, parent::FETCH_ASSOC);
        $this->setAttribute(parent::ATTR_EMULATE_PREPARES, false);
    }

    /**
     * @param string|StatementInterface $statement
     * @param array<string, mixed> $parameters
     * @return StatementInterface
     */
    public function request(string|StatementInterface $statement, array $parameters = []): StatementInterface
    {
        if (is_string($statement)) {
            try {
                $statement = new PdoStatement($this->prepare($statement));
            } catch (PDOException $exception) {
                throw new SqlStatementException($statement, $exception);
            }
        }
        if (!($statement instanceof PdoStatement)) {
            throw new RuntimeException("Statement must be of type string or PdoStatement");
        }

        foreach ($parameters as $fieldName => $value) {
            $statement->bind($fieldName, $value);
        }

        $statement->execute();
        return $statement;
    }

    /**
     * @return bool
     */
    public function startTransaction(): bool
    {
        try {
            $this->setTransaction(true, 0);
            return $this->beginTransaction();
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        try {
            $this->setTransaction(false, 1);
            return parent::commit();
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @return bool
     */
    public function rollback(): bool
    {
        try {
            $this->setTransaction(false, 1);
            return parent::rollback();
        } catch (RuntimeException) {
            return false;
        }
    }

    /**
     * @param bool $target
     * @param int $autoCommit
     * @return void
     */
    private function setTransaction(bool $target, int $autoCommit): void
    {
        if ($this->isTransaction === $target) {
            throw new RuntimeException('Target already set');
        }
        $this->isTransaction = $target;
        $this->setAttribute(parent::ATTR_AUTOCOMMIT, $autoCommit);
    }
}