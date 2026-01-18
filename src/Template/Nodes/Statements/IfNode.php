<?php

namespace Tuto\Template\Nodes\Statements;

use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\Helper\CompileTrait;
use Tuto\Template\Nodes\Helper\ElseBranchTrait;
use Tuto\Template\Nodes\NodeInterface;

class IfNode implements NodeInterface
{
    use CompileTrait;
    use ElseBranchTrait;

    /** @var array<array{condition: ExpressionInterface, body: NodeInterface[]}> $elseIfBranches */
    private array $elseIfBranches = [];

    /**
     * @param ExpressionInterface $condition
     * @param array $body
     */
    public function __construct(
        private readonly ExpressionInterface $condition,
        private readonly array $body,
    ) {
    }

    /**
     * @param ExpressionInterface $condition
     * @param NodeInterface[] $body
     * @return self
     */
    public function addElseIf(ExpressionInterface $condition, array $body): self
    {
        $this->elseIfBranches[] = ['condition' => $condition, 'body' => $body];
        return $this;
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $code = "<?php if ({$this->condition->compile()}): ?>";
        $code .= $this->compileNodes($this->body);

        foreach ($this->elseIfBranches as $branch) {
            $code .= "<?php elseif ({$branch['condition']->compile()}): ?>";
            $code .= $this->compileNodes($branch['body']);
        }

        if ($this->elseBranch !== null) {
            $code .= "<?php else: ?>";
            $code .= $this->compileNodes($this->elseBranch);
        }

        $code .= "<?php endif; ?>";
        return $code;
    }
}
