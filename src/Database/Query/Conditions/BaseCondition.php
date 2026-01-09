<?php

namespace Tuto\Database\Query\Conditions;

use DateTimeInterface;

abstract class BaseCondition
{
    public function __construct(
        protected readonly ConditionType $type,
        protected readonly string $column,
        protected readonly ConditionOperator $operator,
        protected readonly mixed $value,
        protected readonly bool $escape,
    ) {
    }

    /**
     * @return string
     */
    abstract public function render(): string;

    /**
     * @return ConditionType
     */
    public function getType(): ConditionType
    {
        return $this->type;
    }

    /**
     * @param mixed $value
     * @return string
     */
    protected function escapeValue(mixed $value): string
    {
        if ($value === null) {
            return 'null';
        }
        if ($this->escape === false) {
            return $value;
        }
        if (is_numeric($value)) {
            return (string) $value;
        }
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        if ($value instanceof DateTimeInterface) {
            $value = $value->format('Y-m-d H:i:s');
        }
        return "'{$value}'";
    }
}
