<?php

namespace Tuto\Base;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use SplFileInfo;
use Tuto\Container\Items\DependencyItem;

trait ApplicationConfigurable
{
    private string $configPath;

    /**
     * @throws ReflectionException
     */
    private function initConfig(): void
    {
        $this->addEnvFile('.env');
        $this->addEnvFile('.env.local');

        $rdi = new RecursiveDirectoryIterator($this->configPath, FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $config = require $file->getRealPath();
            if (!is_array($config)) {
                continue;
            }
            foreach ($config as $key => $value) {
                if (is_string($key)) {
                    $value = DependencyItem::primitive($key, $value);
                }
                if ($value instanceof DependencyItem) {
                    container()->addDependencyItem($value);
                }
            }
        }
    }

    private function addEnvFile(string $filepath): void
    {
        $path = ROOT . "/{$filepath}";
        if (file_exists($path)) {
            env()?->load($path);
        }
    }
}