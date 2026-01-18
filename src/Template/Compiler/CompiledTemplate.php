<?php

namespace Tuto\Template\Compiler;

use ArrayAccess;
use Tuto\Template\Engine;

abstract class CompiledTemplate
{
    /** @var string|null $parentTemplate */
    protected string|null $parentTemplate = null;

    /** @var array<string, string> $blocks */
    protected array $blocks = [];

    /** @var string[] $blockStack  */
    protected array $blockStack = [];

    /** @var array<string, mixed> */
    protected array $context = [];

    public function __construct(protected readonly Engine $engine)
    {
    }

    /**
     * @param array<string, mixed> $context
     * @return string
     */
    abstract protected function doRender(array $context): string;

    /**
     * @param array<string, mixed> $context
     * @return string
     */
    public function render(array $context = []): string
    {
        $this->context = $context;
        $content = $this->doRender($context);

        if ($this->parentTemplate !== null) {
            return $this->engine->renderWithBlocks(
                $this->parentTemplate,
                $context,
                $this->blocks,
            );
        }

        return $content;
    }

    /**
     * @param string $name
     * @return void
     */
    protected function startBlock(string $name): void
    {
        $this->blockStack[] = $name;
        ob_start();
    }

    /**
     * @param string $name
     * @return void
     */
    protected function endBlock(string $name): void
    {
        $content = ob_get_clean();
        array_pop($this->blockStack);

        if (!isset($this->blocks[$name])) {
            $this->blocks[$name] = $content;
        }
        if ($this->parentTemplate === null) {
            echo $this->blocks[$name];
        }
    }

    /**
     * @param array<string, string> $blocks
     * @return void
     */
    public function setBlocks(array $blocks): void
    {
        $this->blocks = array_merge($blocks, $this->blocks);
    }

    /**
     * @param string $parent
     * @return void
     */
    protected function setParent(string $parent): void
    {
        $this->parentTemplate = $parent;
    }

    /**
     * @param string $template
     * @param array<string, string> $context
     * @return string
     */
    protected function include(string $template, array $context = []): string
    {
        return $this->engine->render($template, $context);
    }

    /**
     * @param string $name
     * @param mixed ...$args
     * @return mixed
     */
    protected function filter(string $name, mixed ...$args): mixed
    {
        return $this->engine->applyFilter($name, ...$args);
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    protected function call(string $name, array $args): mixed
    {
        return $this->engine->callFunction($name, $args);
    }

    /**
     * @param mixed $value
     * @param string|int $key
     * @return mixed
     */
    protected function access(mixed $value, string|int $key): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_array($value)) {
            return $value[$key] ?? null;
        }

        if (is_object($value)) {
            if (property_exists($value, (string) $key)) {
                return $value->{$key};
            }

            $getter = 'get' . ucfirst((string) $key);
            if (method_exists($value, $getter)) {
                return $value->{$getter}();
            }
            $isGetter = 'is' . ucfirst((string) $key);
            if (method_exists($value, $isGetter)) {
                return $value->{$isGetter}();
            }
            $hasGetter = 'has' . ucfirst((string) $key);
            if (method_exists($value, $hasGetter)) {
                return $value->{$hasGetter}();
            }
            if ($value instanceof ArrayAccess) {
                return $value->offsetExists($key) ? $value->offsetGet($key) : null;
            }
        }

        return null;
    }

    protected function getLoopVariable(array $items, mixed $current): object
    {
        $keys = array_keys($items);
        $index = array_search($current, $items, true);
        $count = count($items);

        return (object) [
            'index' => $index,
            'index1' => $index + 1,
            'first' => $index === 0,
            'last' => $index === ($count - 1),
            'length' => $count,
        ];
    }
}
