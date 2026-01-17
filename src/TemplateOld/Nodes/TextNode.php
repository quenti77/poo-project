<?php

namespace Tuto\TemplateOld\Nodes;

class TextNode implements NodeInterface
{
    public function __construct(
        private readonly string $content
    ) {
    }

    public function compile(): string
    {
        return $this->content;
    }
}
