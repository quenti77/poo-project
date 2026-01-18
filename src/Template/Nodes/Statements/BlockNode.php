<?php

namespace Tuto\Template\Nodes\Statements;

use Tuto\Template\Nodes\Helper\CompileTrait;
use Tuto\Template\Nodes\NodeInterface;

class BlockNode implements NodeInterface
{
    use CompileTrait;

    /**
     * @param string $name
     * @param NodeInterface[] $body
     */
    public function __construct(
        private readonly string $name,
        private readonly array $body,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        return <<<PHP
        <?php \$this->startBlock('{$this->name}'); ?>
        {$this->compileNodes($this->body)}
        <?php \$this->endBlock('{$this->name}'); ?>
        PHP;
    }
}
