<?php

namespace Tuto\Template\Nodes;

interface NodeInterface
{
    public function compile(): string;
}
