<?php

namespace Tuto\Database\Query\Conditions;

class RawCondition extends BaseCondition
{
    /**
     * @param ConditionType $type
     * @param string $raw
     */
    public function __construct(ConditionType $type, string $raw)
    {
        parent::__construct($type, $raw, ConditionOperator::EQ, null, false);
    }

    /**
     * @return string
     */
    public function render(): string
    {
        return "{$this->type->value} {$this->column}";
    }
}
