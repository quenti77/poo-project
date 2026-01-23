<?php

namespace Tuto\Template;

use Closure;
use DateTimeImmutable;
use DateTimeInterface;
use RuntimeException;
use Tuto\Template\Compiler\CompiledTemplate;
use Tuto\Template\Compiler\Compiler;
use Tuto\Template\Exceptions\TemplateNotFoundException;
use Tuto\Template\Filters\FilterRegistry;

class Engine
{
    /** @var Lexer $lexer */
    private Lexer $lexer;

    /** @var Parser $parser */
    private Parser $parser;

    /** @var Compiler $compiler */
    private Compiler $compiler;

    /** @var FilterRegistry $filters */
    private FilterRegistry $filters;

    /** @var array<string, Closure> $functions */
    private array $functions = [];

    /** @var array<string, CompiledTemplate> $loadedTemplates */
    private array $loadedTemplates = [];

    /** @var array<string, mixed> $globalVariables */
    private array $globalVariables = [];

    /**
     * @param string $templatePath
     * @param string $cachePath
     * @param bool $debug
     */
    public function __construct(
        private readonly string $templatePath,
        private readonly string $cachePath,
        private readonly bool $debug = false,
    ) {
        $this->lexer = new Lexer();
        $this->parser = new Parser();
        $this->compiler = new Compiler();
        $this->filters = new FilterRegistry();

        $this->registerCoreFilters();
        $this->registerCoreFunctions();
    }

    /**
     * @param string $name
     * @param Closure $filter
     * @return void
     */
    public function addFilter(string $name, Closure $filter): void
    {
        $this->filters->add($name, $filter);
    }

    /**
     * @param string $name
     * @param mixed ...$args
     * @return mixed
     */
    public function applyFilter(string $name, mixed ...$args): mixed
    {
        return $this->filters->apply($name, ...$args);
    }

    /**
     * @param string $name
     * @param Closure $function
     * @return void
     */
    public function addFunction(string $name, Closure $function): void
    {
        $this->functions[$name] = $function;
    }

    /**
     * @param string $name
     * @param array $args
     * @return mixed
     */
    public function callFunction(string $name, array $args): mixed
    {
        if (!isset($this->functions[$name])) {
            throw new RuntimeException("Unknown function: {$name}");
        }

        return ($this->functions[$name])(...$args);
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function addGlobalVariable(string $name, mixed $value): void
    {
        $this->globalVariables[$name] = $value;
    }

    /**
     * @param string $name
     * @param array<string, mixed> $context
     * @return string
     */
    public function render(string $name, array $context = []): string
    {
        $context = [...$this->globalVariables, ...$context];
        return $this->load($name)->render($context);
    }

    /**
     * @param string $name
     * @param array<string, mixed> $context
     * @param array<string, string> $blocks
     * @return string
     */
    public function renderWithBlocks(string $name, array $context, array $blocks): string
    {
        $template = $this->load($name);
        $template->setBlocks($blocks);
        return $template->render($context);
    }

    /**
     * @param string $name
     * @return CompiledTemplate
     */
    public function load(string $name): CompiledTemplate
    {
        if (isset($this->loadedTemplates[$name])) {
            return $this->loadedTemplates[$name];
        }

        $templateFile = "{$this->templatePath}/{$name}";
        $cacheFile = $this->getCacheFile($name);

        if (!file_exists($templateFile)) {
            throw new TemplateNotFoundException("Template not found: {$name}");
        }
        if ($this->needsCompilation($templateFile, $cacheFile)) {
            $this->compileTemplate($name, $templateFile, $cacheFile);
        }

        require_once $cacheFile;
        $className = $this->getClassName($name);

        $this->loadedTemplates[$name] = new $className($this);
        return $this->loadedTemplates[$name];
    }

    /**
     * @return void
     */
    public function clearCache(): void
    {
        $files = glob($this->cachePath . '/*.php');
        foreach ($files as $file) {
            unlink($file);
        }
        $this->loadedTemplates = [];
    }

    /**
     * @param string $templateFile
     * @param string $cacheFile
     * @return bool
     */
    private function needsCompilation(string $templateFile, string $cacheFile): bool
    {
        if ($this->debug) {
            return true;
        }
        if (!file_exists($cacheFile)) {
            return true;
        }
        return filemtime($templateFile) > filemtime($cacheFile);
    }

    /**
     * @param string $name
     * @param string $templateFile
     * @param string $cacheFile
     * @return void
     */
    private function compileTemplate(string $name, string $templateFile, string $cacheFile): void
    {
        $source = file_get_contents($templateFile);

        $tokens = $this->lexer->tokenize($source);
        $nodes = $this->parser->parse($tokens);
        $code = $this->compiler->compile($nodes, $name);

        $dir = dirname($cacheFile);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new RuntimeException(sprintf('Directory "%s" was not created', $dir));
        }

        file_put_contents($cacheFile, $code);
        if (function_exists('opcache_compile_file')) {
            opcache_compile_file($cacheFile);
        }
    }

    /**
     * @param string $name
     * @return string
     */
    private function getCacheFile(string $name): string
    {
        $className = $this->getClassName($name);
        return $this->cachePath . "/{$className}.php";
    }

    /**
     * @param string $name
     * @return string
     */
    private function getClassName(string $name): string
    {
        $hash = md5($name);
        $safe = preg_replace('/[^a-zA-Z0-9]/', '_', $name);
        return "Template_{$safe}_{$hash}";
    }

    /**
     * @return void
     */
    private function registerCoreFilters(): void
    {
        $this->addFilter('upper', mb_strtoupper(...));
        $this->addFilter('lower', mb_strtolower(...));
        $this->addFilter('capitalize', static fn ($v) => $v |> mb_strtolower(...) |> ucfirst(...));
        $this->addFilter('title', static fn ($v) => $v |> mb_strtolower(...) |> ucwords(...));
        $this->addFilter('trim', trim(...));
        $this->addFilter('length', static fn ($v) => is_array($v) ? count($v) : mb_strlen($v ?? ''));

        $this->addFilter('default', static fn ($v, $d = '') => $v ?? $d);
        $this->addFilter('escape', static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'));
        $this->addFilter('raw', static fn ($v) => $v);
        $this->addFilter('json', static fn ($v) => json_encode($v, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));

        $this->addFilter('join', static fn ($v, $g = ', ') => implode($g, (array) $v));
        $this->addFilter('first', static fn ($v) => is_array($v) ? reset($v) : $v);
        $this->addFilter('last', static fn ($v) => is_array($v) ? end($v) : $v);
        $this->addFilter('reverse', static fn ($v) => is_array($v) ? array_reverse($v) : strrev((string) $v));
        $this->addFilter('keys', static fn ($v) => array_keys((array) $v));
        $this->addFilter('values', static fn ($v) => array_values((array) $v));
        $this->addFilter('slice', static fn ($v, $s, $l = null) => is_array($v) ? array_slice($v, $s, $l) : mb_substr((string) $v, $s, $l));

        $this->addFilter('date', static fn ($v, $f = 'Y-m-d') => ($v instanceof DateTimeInterface ? $v : new DateTimeImmutable($v))->format($f));
        $this->addFilter('abs', static fn ($v) => abs($v));
        $this->addFilter('round', static fn ($v, $p = 0) => round($v, $p));
        $this->addFilter('nl2br', static fn ($v) => nl2br((string) $v));
    }

    /**
     * @return void
     */
    private function registerCoreFunctions(): void
    {
        $this->addFunction('range', static fn ($s, $e, $st = 1) => range($s, $e, $st));
        $this->addFunction('cycle', static fn ($a, $i) => $a[$i % count($a)]);
        $this->addFunction('random', static fn ($a) => is_array($a) ? $a[array_rand($a)] : random_int(0, $a));
        $this->addFunction('min', static fn (...$a) => min(...$a));
        $this->addFunction('max', static fn (...$a) => max(...$a));
        $this->addFunction('dump', static fn (...$a) => var_export($a, true));
        $this->addFunction('date', static fn ($f = 'now') => new DateTimeImmutable($f));
    }
}
