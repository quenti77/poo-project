<?php

namespace Tuto\Base;

class Autoloader
{
    /** @var array<string, string> $namespaces */
    private array $namespaces = [];

    public function __construct()
    {
        spl_autoload_register(fn (string $classname) => $this->resolve($classname));
    }

    public function addMapping(string $baseNamespace, string $basePath): void
    {
        $this->namespaces[$baseNamespace] = $basePath;
    }

    private function resolve(string $classname): void
    {
        foreach ($this->namespaces as $baseNamespace => $basePath) {
            if (!str_starts_with($classname, $baseNamespace)) {
                continue;
            }

            $path = str_replace($baseNamespace, $basePath, $classname);
            $path = str_replace('\\', '/', $path);
            require ROOT . "/{$path}.php";
        }
    }
}

$autoloader = new Autoloader();
$autoloader->addMapping('App\\', 'app/');
$autoloader->addMapping('Database\\', 'database/');
$autoloader->addMapping('Tuto\\', 'src/');
