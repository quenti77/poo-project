<?php

namespace Tuto\Template\Nodes\Helper;

use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\NodeInterface;

trait CompileTrait
{
    /**
     * @param NodeInterface[] $nodes
     * @return string
     */
    private function compileNodes(array $nodes): string
    {
        $compiledNodes = array_map(static fn (NodeInterface $node) => $node->compile(), $nodes);
        return implode('', $compiledNodes);
    }

    /**
     * @param ExpressionInterface[] $expressions
     * @return string
     */
    private function compileExpressions(array $expressions): string
    {
        $compiledArgs = array_map(static fn (ExpressionInterface $arg) => $arg->compile(), $expressions);
        return implode(', ', $compiledArgs);
    }
}
