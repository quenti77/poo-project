<?php

namespace Tuto\Template\Nodes\Expressions;

use Tuto\Template\Nodes\ExpressionInterface;

class ArrayExpression implements ExpressionInterface
{
    /**
     * @param array<string | int, ExpressionInterface> $elements
     * @param bool $isHash
     */
    public function __construct(
        private readonly array $elements,
        private readonly bool $isHash = false,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $parts = [];

        foreach ($this->elements as $key => $value) {
            $compiled = $value->compile();

            if ($this->isHash) {
                $keyStr = is_string($key) ? "'{$key}'" : $key;
                $parts[] = "{$keyStr} => {$compiled}";
            } else {
                $parts[] = $compiled;
            }
        }

        return '[' . implode(', ', $parts) . ']';
    }
}
