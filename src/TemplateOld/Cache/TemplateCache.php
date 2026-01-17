<?php

namespace Tuto\TemplateOld\Cache;

class TemplateCache
{
    public function __construct(
        private readonly string $cachePath,
        private readonly bool $debug = false
    ) {
        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0755, true);
        }
    }

    public function has(string $templatePath): bool
    {
        $cachePath = $this->getCachePath($templatePath);

        if (!file_exists($cachePath)) {
            return false;
        }

        if ($this->debug) {
            return filemtime($cachePath) >= filemtime($templatePath);
        }

        return true;
    }

    public function get(string $templatePath): string
    {
        return $this->getCachePath($templatePath);
    }

    public function put(string $templatePath, string $compiledCode): string
    {
        $cachePath = $this->getCachePath($templatePath);
        file_put_contents($cachePath, $compiledCode);
        return $cachePath;
    }

    public function clear(): void
    {
        $files = glob($this->cachePath . '/Template_*.php');
        if ($files === false) {
            return;
        }

        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function getCachePath(string $templatePath): string
    {
        $hash = md5($templatePath);
        return $this->cachePath . '/Template_' . $hash . '.php';
    }
}
