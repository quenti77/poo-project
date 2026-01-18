<?php

namespace Tuto\Template\Nodes\Expressions;

use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\Helper\CompileTrait;

class FilterExpression implements ExpressionInterface
{
    use CompileTrait;

    /**
     * @param ExpressionInterface $expression
     * @param string $filterName
     * @param ExpressionInterface[] $arguments
     */
    public function __construct(
        private readonly ExpressionInterface $expression,
        private readonly string $filterName,
        private readonly array $arguments = [],
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $arguments = [$this->expression, ...$this->arguments];
        $args = $this->compileExpressions($arguments);

        return "\$this->filter('{$this->filterName}', {$args})";
    }
}
