<?php

namespace Tuto\Template\Nodes\Expressions;

use InvalidArgumentException;
use Tuto\Template\Nodes\ExpressionInterface;

class LiteralExpression implements ExpressionInterface
{
    /**
     * @param mixed $value
     */
    public function __construct(private readonly mixed $value)
    {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        return match (true) {
            is_null($this->value) => 'null',
            is_bool($this->value) => $this->value ? 'true' : 'false',
            is_string($this->value) => "'" . addslashes($this->value) . "'",
            is_int($this->value), is_float($this->value) => (string) $this->value,
            default => throw new InvalidArgumentException("Unsupported literal type"),
        };
    }
}
