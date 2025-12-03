<?php

namespace Tuto\Base;

class Autoloader
{
    /** @var array<string, string> $mappings */
    private array $mappings = [];

    public function __construct()
    {
        spl_autoload_register(fn (string $className) => $this->resolve($className));
    }

    /**
     * Adding a new mapping namespace with folder
     *
     * @param string $baseNamespace
     * @param string $basePath
     * @return void
     */
    public function addMapping(string $baseNamespace, string $basePath): void
    {
        $this->mappings[$baseNamespace] = $basePath;
    }

    /**
     * Used when PHP attempts to autoload the file corresponding to the requested class
     *
     * @param class-string $className
     * @return void
     */
    private function resolve(string $className): void
    {
        foreach ($this->mappings as $baseNamespace => $basePath) {
            if (!str_starts_with($className, $baseNamespace)) {
                continue;
            }

            // example: $className = \Tuto\Base\Autoloader
            // $path = src/Base\Autoloader
            $path = str_replace($baseNamespace, $basePath, $className);

            // $path = src/Base/Autoloader
            $path = str_replace('\\', '/', $path);

            // require "/path/to/project/src/Base/Autoloader.php"
            require ROOT . "/{$path}.php";
        }
    }
}

// When this autoloader is loaded (via "require"), these namespace mappings are initialized
$autoloader = new Autoloader();
$autoloader->addMapping('App\\', 'app/');
$autoloader->addMapping('Database\\', 'database/');
$autoloader->addMapping('Tuto\\', 'src/');
