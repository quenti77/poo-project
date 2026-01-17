<?php

namespace Tuto\Template\Nodes;

class PrintNode implements NodeInterface
{
    public function __construct(
        private readonly string $expression,
        private readonly bool $autoEscape = true
    ) {
    }

    public function compile(): string
    {
        $expr = trim($this->expression);

        $hasRaw = str_contains($expr, '|raw') || str_contains($expr, '| raw');

        if ($this->autoEscape && !$hasRaw) {
            if (!str_contains($expr, '|escape') && !str_contains($expr, '| escape')
                && !str_contains($expr, '|e') && !str_contains($expr, '| e')) {
                $expr .= ' | escape';
            }
        }

        return '<?php echo $this->evaluate(' . var_export($expr, true) . '); ?>';
    }
}
