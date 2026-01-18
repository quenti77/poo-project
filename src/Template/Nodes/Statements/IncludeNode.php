<?php

namespace Tuto\Template\Nodes\Statements;

use Tuto\Template\Nodes\ExpressionInterface;
use Tuto\Template\Nodes\NodeInterface;

class IncludeNode implements NodeInterface
{
    /**
     * @param ExpressionInterface|string $template
     * @param array<string, ExpressionInterface>|null $variables
     * @param bool $withContext
     */
    public function __construct(
        private readonly ExpressionInterface|string $template,
        private readonly array|null $variables = null,
        private readonly bool $withContext = true,
    ) {
    }

    /**
     * @return string
     */
    public function compile(): string
    {
        $template = is_string($this->template)
            ? "'{$this->template}'"
            : $this->template->compile();

        $contextCode = $this->withContext ? '$this->context' : '[]';

        if ($this->variables !== null) {
            $vars = [];
            foreach ($this->variables as $name => $expr) {
                $vars[] = "'{$name}' => {$expr->compile()}";
            }
            $varsCode = '[' . implode(', ', $vars) . ']';
            $contextCode = "array_merge({$contextCode}, {$varsCode})";
        }

        return "<?= \$this->include({$template}, {$contextCode}) ?>";
    }
}
