<?php

namespace Tuto\Template\Nodes\Expressions;

use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\Helper\CompileTrait;

class FunctionCallExpression implements ExpressionInterface
{
    use CompileTrait;

    /**
     * @param string $name
     * @param ExpressionInterface[] $arguments
     */
    public function __construct(
        private readonly string $name,
        private readonly array $arguments = [],
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $args = $this->compileExpressions($this->arguments);
        return "\$this->call('{$this->name}', [{$args}])";
    }
}
