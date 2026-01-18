<?php

namespace Tuto\Template\Nodes\Statements;

use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\Helper\CompileTrait;
use Tuto\Template\Nodes\Helper\ElseBranchTrait;
use Tuto\Template\Nodes\NodeInterface;

class ForNode implements NodeInterface
{
    use CompileTrait;
    use ElseBranchTrait;

    /**
     * @param string|null $keyVariable
     * @param string $valueVariable
     * @param ExpressionInterface $iterable
     * @param NodeInterface[] $body
     */
    public function __construct(
        private readonly string|null $keyVariable,
        private readonly string $valueVariable,
        private readonly ExpressionInterface $iterable,
        private readonly array $body,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $iterable = $this->iterable->compile();
        $loopVar = '$__loop_' . str_replace('.', '_', uniqid('', true));

        $loopVars = $this->keyVariable === null
            ? "\${$this->valueVariable}"
            : "\${$this->keyVariable} => \${$this->valueVariable}";

        $code = "<?php {$loopVar} = {$iterable}; ?>";
        if ($this->elseBranch !== null) {
            $code .= "<?php if (!empty({$loopVar})): ?>";
        }
        $code .= "<?php foreach ({$loopVar} as {$loopVars}): ?>";

        $code .= "<?php \$loop = \$this->getLoopVariable({$loopVar}, \${$this->valueVariable}); ?>";
        $code .= $this->compileNodes($this->body);
        $code .= "<?php endforeach; ?>";

        if ($this->elseBranch !== null) {
            $code .= "<?php else: ?>";
            $code .= $this->compileNodes($this->elseBranch);
            $code .= "<?php endif; ?>";
        }

        return $code;
    }
}
