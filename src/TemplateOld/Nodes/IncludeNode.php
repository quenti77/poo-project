<?php

namespace Tuto\TemplateOld\Nodes;

class IncludeNode implements NodeInterface
{
    public function __construct(
        private readonly string $template,
        private readonly ?string $withContext = null
    ) {
    }

    public function compile(): string
    {
        $context = $this->withContext
            ? '$this->evaluate(' . var_export($this->withContext, true) . ')'
            : '[]';

        return '<?php echo $this->includeTemplate('
            . var_export($this->template, true)
            . ', ' . $context . '); ?>';
    }
}
