<?php

namespace Tuto\Template\Nodes;

class ExtendsNode implements NodeInterface
{
    public function __construct(
        private readonly string $parent
    ) {
    }

    public function getParent(): string
    {
        return $this->parent;
    }

    public function compile(): string
    {
        return '<?php $this->setParent(' . var_export($this->parent, true) . '); ?>';
    }
}
