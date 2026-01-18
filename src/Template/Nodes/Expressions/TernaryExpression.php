<?php

namespace Tuto\Template\Nodes\Expressions;

use Tuto\Template\Nodes\ExpressionInterface;

class TernaryExpression implements ExpressionInterface
{
    /**
     * @param ExpressionInterface $condition
     * @param ExpressionInterface|null $trueBranch
     * @param ExpressionInterface $falseBranch
     */
    public function __construct(
        private readonly ExpressionInterface $condition,
        private readonly ExpressionInterface|null $trueBranch,
        private readonly ExpressionInterface $falseBranch,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $condition = $this->condition->compile();
        $false = $this->falseBranch->compile();

        if ($this->trueBranch === null) {
            return "({$condition} ?: {$false})";
        }

        $true = $this->trueBranch->compile();
        return "({$condition}) ? {$true} : {$false}";
    }
}
