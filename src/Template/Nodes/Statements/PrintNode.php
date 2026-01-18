<?php

namespace Tuto\Template\Nodes\Statements;

use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\NodeInterface;

class PrintNode implements NodeInterface
{
    /**
     * @param ExpressionInterface $expression
     * @param bool $escape
     */
    public function __construct(
        private readonly ExpressionInterface $expression,
        private readonly bool $escape = true,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $compiled = $this->expression->compile();

        return $this->escape
            ? "<?= htmlspecialchars({$compiled}, ENT_QUOTES, 'UTF-8') ?>"
            : "<?= {$compiled} ?>";
    }
}
