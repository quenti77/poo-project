<?php

namespace Tuto\Database\Pdo;

use BackedEnum;
use DateTimeInterface;
use JsonException;
use PDO;
use Tuto\Database\StatementInterface;
use Tuto\Utils\ValueObject\Ulid;
use UnitEnum;

class PdoStatement implements StatementInterface
{
    private const array TYPE = [
        'integer' => PDO::PARAM_INT,
        'boolean' => PDO::PARAM_BOOL,
    ];

    public function __construct(private readonly \PDOStatement $statement)
    {
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return bool
     * @throws JsonException
     */
    public function bind(string $name, mixed $value): bool
    {
        $parameterType = gettype($value);
        $bindType = PDO::PARAM_STR;

        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        } elseif ($value instanceof Ulid) {
            $value = (string) $value;
        } elseif ($value instanceof BackedEnum) {
            $value = $value->value;
        } elseif ($value instanceof UnitEnum) {
            $value = $value->name;
        } elseif (is_array($value)) {
            $value = json_encode($value, JSON_THROW_ON_ERROR);
        } elseif (array_key_exists($parameterType, self::TYPE)) {
            $bindType = self::TYPE[$parameterType];
        } elseif ($value === null) {
            $bindType = PDO::PARAM_NULL;
        }

        return $this->statement->bindValue($name, $value, $bindType);
    }

    /**
     * @return bool
     */
    public function execute(): bool
    {
        return $this->statement->execute();
    }

    /**
     * @return array<string, mixed>|false
     */
    public function fetch(): array|false
    {
        return $this->statement->fetch();
    }

    /**
     * @return array<array<string, mixed>>
     */
    public function fetchAll(): array
    {
        return $this->statement->fetchAll();
    }
}
