<?php

namespace Tuto\Template\Nodes\Statements;

use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\NodeInterface;

class SetNode implements NodeInterface
{
    /**
     * @param string $variableName
     * @param ExpressionInterface $value
     */
    public function __construct(
        private readonly string $variableName,
        private readonly ExpressionInterface $value,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $compiled = $this->value->compile();
        return "<?php \${$this->variableName} = {$compiled}; ?>";
    }
}
