<?php

namespace Tuto\TemplateOld\Nodes;

class BlockNode implements NodeInterface
{
    /** @var NodeInterface[] */
    private array $body = [];

    public function __construct(
        private readonly string $name
    ) {
    }

    public function setBody(array $body): void
    {
        $this->body = $body;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function compile(): string
    {
        $output = '<?php $this->startBlock(' . var_export($this->name, true) . '); ?>';

        foreach ($this->body as $node) {
            $output .= $node->compile();
        }

        $output .= '<?php $this->endBlock(); ?>';

        return $output;
    }
}
