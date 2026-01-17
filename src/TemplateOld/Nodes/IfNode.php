<?php

namespace Tuto\TemplateOld\Nodes;

class IfNode implements NodeInterface
{
    /** @var array{condition: string, body: NodeInterface[]}[] */
    private array $branches = [];

    /** @var NodeInterface[] */
    private array $elseBranch = [];

    public function addBranch(string $condition, array $body): void
    {
        $this->branches[] = [
            'condition' => $condition,
            'body' => $body
        ];
    }

    public function setElseBranch(array $body): void
    {
        $this->elseBranch = $body;
    }

    public function compile(): string
    {
        $output = '';

        foreach ($this->branches as $index => $branch) {
            $keyword = $index === 0 ? 'if' : 'elseif';
            $condition = $this->compileCondition($branch['condition']);

            $output .= "<?php {$keyword} ({$condition}): ?>";

            foreach ($branch['body'] as $node) {
                $output .= $node->compile();
            }
        }

        if (!empty($this->elseBranch)) {
            $output .= '<?php else: ?>';
            foreach ($this->elseBranch as $node) {
                $output .= $node->compile();
            }
        }

        $output .= '<?php endif; ?>';

        return $output;
    }

    private function compileCondition(string $condition): string
    {
        $condition = preg_replace('/\band\b/', '&&', $condition);
        $condition = preg_replace('/\bor\b/', '||', $condition);
        $condition = preg_replace('/\bnot\b/', '!', $condition);

        return '$this->evaluateCondition(' . var_export(trim($condition), true) . ')';
    }
}
