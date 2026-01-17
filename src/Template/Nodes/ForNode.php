<?php

namespace Tuto\Template\Nodes;

class ForNode implements NodeInterface
{
    /** @var NodeInterface[] */
    private array $body = [];

    /** @var NodeInterface[] */
    private array $elseBody = [];

    public function __construct(
        private readonly string $valueVar,
        private readonly string $iterable,
        private readonly ?string $keyVar = null
    ) {
    }

    public function setBody(array $body): void
    {
        $this->body = $body;
    }

    public function setElseBody(array $body): void
    {
        $this->elseBody = $body;
    }

    public function compile(): string
    {
        $iterableExpr = '$this->evaluate(' . var_export($this->iterable, true) . ')';

        $output = "<?php \$__iterable = {$iterableExpr}; ?>";
        $output .= '<?php $__length = is_countable($__iterable) ? count($__iterable) : 0; ?>';
        $output .= '<?php $__index = 0; ?>';
        $output .= '<?php if (!empty($__iterable)): ?>';

        if ($this->keyVar) {
            $output .= "<?php foreach (\$__iterable as \${$this->keyVar} => \${$this->valueVar}): ?>";
        } else {
            $output .= "<?php foreach (\$__iterable as \${$this->valueVar}): ?>";
        }

        $output .= '<?php $__index++; ?>';
        $output .= '<?php $loop = [\'index\' => $__index, \'index0\' => $__index - 1, \'length\' => $__length, \'first\' => $__index === 1, \'last\' => $__index === $__length]; ?>';

        $output .= '<?php $this->pushContext([';
        $output .= "'{$this->valueVar}' => \${$this->valueVar}, 'loop' => \$loop";
        if ($this->keyVar) {
            $output .= ", '{$this->keyVar}' => \${$this->keyVar}";
        }
        $output .= ']); ?>';

        foreach ($this->body as $node) {
            $output .= $node->compile();
        }

        $output .= '<?php $this->popContext(); ?>';
        $output .= '<?php endforeach; ?>';

        if (!empty($this->elseBody)) {
            $output .= '<?php else: ?>';
            foreach ($this->elseBody as $node) {
                $output .= $node->compile();
            }
        }

        $output .= '<?php endif; ?>';

        return $output;
    }
}
