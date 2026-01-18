<?php

namespace Tuto\Template\Nodes\Statements;

use Tuto\Template\Nodes\NodeInterface;

class TextNode implements NodeInterface
{
    /**
     * @param string $content
     */
    public function __construct(private readonly string $content)
    {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        return $this->content;
    }
}
