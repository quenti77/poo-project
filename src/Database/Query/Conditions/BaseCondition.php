<?php

namespace Tuto\Database\Query\Conditions;

abstract class BaseCondition
{
    public function __construct(
        protected readonly ConditionType $type,
        protected readonly string $column,
        protected readonly ConditionOperator $operator,
        protected readonly mixed $value,
    ) {
    }

    abstract public function render(): string;
}