<?php

namespace Tuto\Base;

use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use SplFileInfo;
use Tuto\Container\Items\DependencyItem;
use Tuto\Http\Request;
use Tuto\Http\Response\AbstractResponse;
use Tuto\Http\Response\JsonResponse;
use Tuto\Routing\Router;

class Application
{
    /**
     * @throws ReflectionException
     */
    public function __construct(private readonly string $configPath)
    {
        $this->initConfig();
        $this->initRouter();
    }

    /**
     * @throws JsonException
     * @throws ReflectionException
     */
    public function run(Request $request): AbstractResponse
    {
        $route = router()->match($request);
        if ($route === null) {
            return new JsonResponse(404, ['error' => true, 'message' => 'Not Found']);
        }

        $resolver = container()->resolver();
        $context = $route->getMatches()->all();

        $response = is_array($route->getHandler())
            ? $resolver->resolveArray($route->getHandler(), $context)
            : $resolver->resolveCallable($route->getHandler(), $context);

        if (!($response instanceof AbstractResponse)) {
            $response = new JsonResponse(200, $response);
        }
        return $response;
    }

    public function render(AbstractResponse $response): never
    {
        request()->cookies->export();

        $response->renderHeaders();
        echo $response->getBody();
        exit;
    }

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
                    $value->add(container());
                }
            }
        }
    }

    /**
     * @throws ReflectionException
     */
    private function initRouter(): void
    {
        $rdi = new RecursiveDirectoryIterator(container('path.router'), FilesystemIterator::SKIP_DOTS);
        $rii = new RecursiveIteratorIterator($rdi);

        $context = static function (Router $router, string $configFile) {
            require $configFile;
        };

        /** @var SplFileInfo $file */
        foreach ($rii as $file) {
            if ($file->getExtension() === 'php') {
                $context(router(), $file->getRealPath());
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