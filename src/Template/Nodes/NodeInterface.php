<?php

namespace Tuto\Template\Nodes;

interface NodeInterface
{
    /**
     * @return string
     */
    public function compile(): string;
}
