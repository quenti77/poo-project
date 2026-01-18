<?php

namespace Tuto\Template\Compiler;

use Tuto\Collections\Collection;
use Tuto\Template\Nodes\NodeInterface;
use Tuto\Template\Nodes\Statements\ExtendsNode;

class Compiler
{
    /**
     * @param Collection<int, NodeInterface> $nodes
     * @param string $templateName
     * @return string
     */
    public function compile(Collection $nodes, string $templateName): string
    {
        $className = $this->generateClassName($templateName);
        $extendsNode = $this->findExtendsNode($nodes);

        $body = $nodes
            ->filter(static fn (int $key, NodeInterface $node) => !($node instanceof ExtendsNode))
            ->map(static fn (int $key, NodeInterface $node) => $node->compile())
            ->join();

        $parentTemplate = $extendsNode !== null
            ? "'{$extendsNode->getParentTemplate()}'"
            : 'null';

        return <<<PHP
        <?php

        declare(strict_types=1);

        use Tuto\Template\Compiler\CompiledTemplate;

        /**
         * Auto-generated template class
         * Source: {$templateName}
         * Generated: {$this->timestamp()}
         */
        class {$className} extends CompiledTemplate
        {
            /** @var string|null \$parentTemplate */
            protected string|null \$parentTemplate = {$parentTemplate};

            /**
             * @param array \$context
             * @return string
             */
            protected function doRender(array \$context): string
            {
                extract(\$context);
                ob_start();

                try {
                    ?>{$body}<?php
                    return ob_get_clean();
                } catch (Throwable \$exception) {
                    ob_end_clean();
                    throw \$exception;
                }
            }
        }
        PHP;
    }

    /**
     * @param string $templateName
     * @return string
     */
    private function generateClassName(string $templateName): string
    {
        $hash = md5($templateName);
        $safe = preg_replace('/[^a-zA-Z0-9]/', '_', $templateName);
        return "Template_{$safe}_{$hash}";
    }

    /**
     * @param Collection $nodes
     * @return ExtendsNode|null
     */
    private function findExtendsNode(Collection $nodes): ExtendsNode|null
    {
        return $nodes->find(static fn (int $key, NodeInterface $node) => $node instanceof ExtendsNode);
    }

    /**
     * @return string
     */
    private function timestamp(): string
    {
        return date('Y-m-d H:i:s');
    }
}
