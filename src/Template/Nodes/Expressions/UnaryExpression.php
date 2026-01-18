<?php

namespace Tuto\Template\Nodes\Expressions;

use InvalidArgumentException;
use Tuto\Template\Nodes\ExpressionInterface;

class UnaryExpression implements ExpressionInterface
{
    /**
     * @param string $operator
     * @param ExpressionInterface $operand
     */
    public function __construct(
        private readonly string $operator,
        private readonly ExpressionInterface $operand,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $operand = $this->operand->compile();

        return match ($this->operator) {
            'not', '!' => "(!{$operand})",
            '-' => "(-{$operand})",
            '+' => "(+{$operand})",
            default => throw new InvalidArgumentException("Unknown unary operator '{$this->operator}'"),
        };
    }
}
