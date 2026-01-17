<?php

namespace Tuto\Template;

use Tuto\Collections\Collection;
use Tuto\Template\Nodes\NodeInterface;

class Compiler
{
    private string $className = '';

    /**
     * @param Collection<int, NodeInterface> $nodes
     * @param string $templateName
     * @return string
     */
    public function compile(Collection $nodes, string $templateName): string
    {
        $this->className = $this->generateClassName($templateName);

        $body = '';
        foreach ($nodes as $node) {
            $body .= $node->compile();
        }

        return <<<PHP
<?php

use Tuto\Template\CompiledTemplate;

class {$this->className} extends CompiledTemplate
{
    protected function doRender(): void
    {
        ?>$body<?php
    }
}
PHP;
    }

    public function getClassName(): string
    {
        return $this->className;
    }

    private function generateClassName(string $templateName): string
    {
        $hash = md5($templateName);
        $name = preg_replace('/[^a-zA-Z0-9]/', '_', $templateName);
        return 'Template_' . $name . '_' . substr($hash, 0, 8);
    }
}
