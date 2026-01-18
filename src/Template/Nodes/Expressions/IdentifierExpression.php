<?php

namespace Tuto\Template\Nodes\Expressions;

use Tuto\Template\Nodes\ExpressionInterface;

class IdentifierExpression implements ExpressionInterface
{
    /**
     * @param string $name
     */
    public function __construct(private readonly string $name)
    {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        return '$' . $this->name;
    }
}
