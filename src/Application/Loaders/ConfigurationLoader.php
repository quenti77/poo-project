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
    /**
     * @param string $configPath Target all configuration files (Default: ./config/)
     */
    public function __construct(private readonly string $configPath)
    {
    }

    /**
     * @return void
     * @throws ReflectionException
     */
    public function load(): void
    {
        // Find all files in config folder
        $rdi = new RecursiveDirectoryIterator($this->configPath, FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            // If it is not a PHP file
            if ($file->getExtension() !== 'php') {
                continue;
            }

            // We can use return in PHP file outside function or method
            $config = require $file->getRealPath();
            if (!is_array($config)) {
                // Config must be returned an array
                continue;
            }

            $this->readConfigArray($config);
        }
    }

    /**
     * @param array<string | int, string | DependencyItem> $config
     * @return void
     */
    private function readConfigArray(array $config): void
    {
        foreach ($config as $key => $value) {
            if (is_string($key)) { // 'key' => value
                $value = DependencyItem::primitive($key, $value);
            }
            if ($value instanceof DependencyItem) {
                container()->addDependencyItem($value);
            }
        }
    }
}
