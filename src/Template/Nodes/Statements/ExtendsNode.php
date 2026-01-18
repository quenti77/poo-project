<?php

namespace Tuto\Template\Nodes\Statements;

use Tuto\Template\Nodes\NodeInterface;

class ExtendsNode implements NodeInterface
{
    /**
     * @param string $parentTemplate
     */
    public function __construct(private readonly string $parentTemplate)
    {
    }

    /**
     * @return string
     */
    public function getParentTemplate(): string
    {
        return $this->parentTemplate;
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        return "<?php \$this->setParent('{$this->parentTemplate}'); ?>";
    }
}
