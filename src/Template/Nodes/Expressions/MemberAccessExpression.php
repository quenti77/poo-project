<?php

namespace Tuto\Template\Nodes\Expressions;

use Tuto\Template\Nodes\ExpressionInterface;

class MemberAccessExpression implements ExpressionInterface
{
    /**
     * @param ExpressionInterface $object
     * @param ExpressionInterface|string $property
     */
    public function __construct(
        private readonly ExpressionInterface $object,
        private readonly ExpressionInterface|string $property,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $object = $this->object->compile();

        if ($this->property instanceof ExpressionInterface) {
            $key = $this->property->compile();
            return "\$this->access({$object}, {$key})";
        }

        return "\$this->access({$object}, '{$this->property}')";
    }
}
