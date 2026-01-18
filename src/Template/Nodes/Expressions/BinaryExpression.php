<?php

namespace Tuto\Template\Nodes\Expressions;

use Tuto\Template\Nodes\ExpressionInterface;

class BinaryExpression implements ExpressionInterface
{
    /** @var array<string, string> */
    private const array OPERATOR_MAP = [
        'and' => '&&',
        'or' => '||',
        'not' => '!',
        '==' => '===',
        '!=' => '!==',
        '..' => 'range',
    ];

    public function __construct(
        private readonly ExpressionInterface $left,
        private readonly string $operator,
        private readonly ExpressionInterface $right,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $left = $this->left->compile();
        $right = $this->right->compile();

        if ($this->operator === '..') {
            return "range({$left}, {$right})";
        }
        if ($this->operator === '~') {
            return "({$left} . {$right})";
        }
        if ($this->operator === 'in') {
            return "in_array({$left}, {$right}, true)";
        }
        if ($this->operator === 'not in') {
            return "!in_array({$left}, {$right}, true)";
        }

        $op = self::OPERATOR_MAP[$this->operator] ?? $this->operator;
        return "({$left} {$op} {$right})";
    }
}
