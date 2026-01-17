<?php

namespace Tuto\Template;

use Closure;
use RuntimeException;
use Tuto\Template\Cache\TemplateCache;

class Engine
{
    private Lexer $lexer;
    private Parser $parser;
    private Compiler $compiler;
    private FilterRegistry $filters;
    private ExpressionEvaluator $evaluator;
    private TemplateCache $cache;

    /** @var array<string, CompiledTemplate> */
    private array $loadedTemplates = [];

    public function __construct(
        private readonly string $templatePath,
        private readonly string $cachePath,
        private readonly bool $debug = false
    ) {
        $this->lexer = new Lexer();
        $this->parser = new Parser();
        $this->compiler = new Compiler();
        $this->filters = new FilterRegistry(collect());
        $this->evaluator = new ExpressionEvaluator($this->filters);
        $this->cache = new TemplateCache($cachePath, $debug);

        CoreFilters::register($this->filters);
    }

    /**
     * Enregistre un filtre personnalisé.
     */
    public function addFilter(string $name, Closure $filter): void
    {
        $this->filters->register($name, $filter);
    }

    /**
     * Ajoute une classe autorisée pour l'instanciation.
     */
    public function addAllowedClass(string $alias, string $className): void
    {
        $this->evaluator->addAllowedClass($alias, $className);
    }

    /**
     * Rend un template avec le contexte donné.
     */
    public function render(string $name, array $context = [], array $blocks = []): string
    {
        $template = $this->load($name);

        if (!empty($blocks)) {
            $template->setBlocks($blocks);
        }

        return $template->render($context);
    }

    /**
     * Charge et compile un template.
     */
    public function load(string $name): CompiledTemplate
    {
        if (isset($this->loadedTemplates[$name])) {
            $template = clone $this->loadedTemplates[$name];
            $template->setEngine($this);
            return $template;
        }

        $templatePath = $this->resolvePath($name);

        if (!$this->cache->has($templatePath)) {
            $this->compileTemplate($templatePath);
        }

        $cachePath = $this->cache->get($templatePath);
        require_once $cachePath;

        $className = $this->getClassName($cachePath);

        /** @var CompiledTemplate $template */
        $template = new $className();
        $template->setEngine($this);

        $this->loadedTemplates[$name] = $template;

        return $template;
    }

    public function getEvaluator(): ExpressionEvaluator
    {
        return $this->evaluator;
    }

    public function getFilters(): FilterRegistry
    {
        return $this->filters;
    }

    public function clearCache(): void
    {
        $this->cache->clear();
        $this->loadedTemplates = [];
    }

    private function compileTemplate(string $templatePath): void
    {
        $source = file_get_contents($templatePath);

        if ($source === false) {
            throw new RuntimeException("Cannot read template: $templatePath");
        }

        $tokens = $this->lexer->tokenize($source);
        $nodes = $this->parser->parse($tokens);
        $compiled = $this->compiler->compile($nodes, $templatePath);

        $this->cache->put($templatePath, $compiled);
    }

    private function resolvePath(string $name): string
    {
        $path = $this->templatePath . '/' . $name;

        if (!file_exists($path)) {
            throw new RuntimeException("Template not found: $name (looked in $path)");
        }

        return $path;
    }

    private function getClassName(string $cachePath): string
    {
        $content = file_get_contents($cachePath);
        if ($content !== false && preg_match('/class\s+(\w+)\s+extends/', $content, $matches)) {
            return $matches[1];
        }

        throw new RuntimeException("Cannot determine class name for: $cachePath");
    }
}
