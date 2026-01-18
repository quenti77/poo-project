<?php

namespace Tuto\Template\Nodes\Helper;

use Tuto\Template\Nodes\NodeInterface;

trait ElseBranchTrait
{
    /** @var NodeInterface[]|null $elseBranch */
    private array|null $elseBranch = null;

    /**
     * @param NodeInterface[] $body
     * @return static
     */
    public function setElse(array $body): static
    {
        $this->elseBranch = $body;
        return $this;
    }
}
