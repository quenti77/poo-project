<?php

namespace Tuto\Database\Query\Conditions;

use Tuto\Collections\Collection;

class GroupCondition extends BaseCondition
{
    /**
     * @param ConditionType $type
     * @param Collection<int, BaseCondition> $conditions
     */
    public function __construct(ConditionType $type, private readonly Collection $conditions)
    {
        parent::__construct($type, '', ConditionOperator::EQ, '', false);
    }

    public function render(): string
    {
        $renderParts = collect();
        foreach ($this->conditions as $condition) {
            $renderCondition = $condition->render();
            if ($renderParts->isEmpty()) {
                $renderCondition = str_replace($condition->getType()->value . ' ', '', $renderCondition);
            }
            $renderParts->push($renderCondition);
        }

        return "{$this->type->value} ({$renderParts->join(' ')})";
    }
}
