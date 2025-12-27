<?php

namespace Tuto\Application\Loaders;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use SplFileInfo;
use Tuto\Container\Items\DependencyItem;

class ConfigurationLoader implements LoaderInterface
{
    public function __construct(private readonly string $configPath)
    {
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function load(): void
    {
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

            $this->readConfigArray($config);
        }
    }

    /**
     * @param array $config
     * @return void
     */
    private function readConfigArray(array $config): void
    {
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