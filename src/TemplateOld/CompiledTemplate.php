<?php

namespace Tuto\TemplateOld;

abstract class CompiledTemplate
{
    protected array $context = [];
    protected array $contextStack = [];
    protected array $blocks = [];
    protected array $blockStack = [];
    protected ?string $parent = null;

    protected ExpressionEvaluator $evaluator;
    protected Engine $engine;

    public function setEngine(Engine $engine): void
    {
        $this->engine = $engine;
        $this->evaluator = $engine->getEvaluator();
    }

    public function setBlocks(array $blocks): void
    {
        $this->blocks = $blocks;
    }

    public function render(array $context): string
    {
        $this->context = $context;

        ob_start();
        $this->doRender();
        $content = ob_get_clean();

        if ($this->parent !== null) {
            return $this->engine->render($this->parent, $context, $this->blocks);
        }

        return $content;
    }

    abstract protected function doRender(): void;

    protected function evaluate(string $expression): mixed
    {
        return $this->evaluator->evaluate($expression, $this->context);
    }

    protected function evaluateCondition(string $condition): bool
    {
        $operators = ['===', '!==', '==', '!=', '>=', '<=', '>', '<'];

        foreach ($operators as $op) {
            if (str_contains($condition, $op)) {
                [$left, $right] = array_map('trim', explode($op, $condition, 2));
                $leftVal = $this->evaluate($left);
                $rightVal = $this->evaluate($right);

                return match ($op) {
                    '===' => $leftVal === $rightVal,
                    '!==' => $leftVal !== $rightVal,
                    '==' => $leftVal == $rightVal,
                    '!=' => $leftVal != $rightVal,
                    '>=' => $leftVal >= $rightVal,
                    '<=' => $leftVal <= $rightVal,
                    '>' => $leftVal > $rightVal,
                    '<' => $leftVal < $rightVal,
                };
            }
        }

        if (str_contains($condition, '&&')) {
            $parts = explode('&&', $condition);
            foreach ($parts as $part) {
                if (!$this->evaluateCondition(trim($part))) {
                    return false;
                }
            }
            return true;
        }

        if (str_contains($condition, '||')) {
            $parts = explode('||', $condition);
            foreach ($parts as $part) {
                if ($this->evaluateCondition(trim($part))) {
                    return true;
                }
            }
            return false;
        }

        if (str_starts_with($condition, '!')) {
            return !$this->evaluateCondition(substr($condition, 1));
        }

        $value = $this->evaluate($condition);
        return !empty($value);
    }

    protected function pushContext(array $vars): void
    {
        $this->contextStack[] = $this->context;
        $this->context = array_merge($this->context, $vars);
    }

    protected function popContext(): void
    {
        $this->context = array_pop($this->contextStack) ?? [];
    }

    protected function setParent(string $template): void
    {
        $this->parent = $template;
    }

    protected function startBlock(string $name): void
    {
        $this->blockStack[] = $name;
        ob_start();
    }

    protected function endBlock(): void
    {
        $name = array_pop($this->blockStack);
        $content = ob_get_clean();

        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = $content;
        }

        echo $this->blocks[$name];
    }

    protected function block(string $name): string
    {
        return $this->blocks[$name] ?? '';
    }

    protected function includeTemplate(string $template, array $context = []): string
    {
        $mergedContext = array_merge($this->context, $context);
        return $this->engine->render($template, $mergedContext);
    }
}
