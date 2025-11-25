<?php

namespace Tuto\Base;

use FilesystemIterator;
use JsonException;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionException;
use SplFileInfo;
use Tuto\Http\Request;
use Tuto\Http\Response\AbstractResponse;
use Tuto\Http\Response\JsonResponse;
use Tuto\Routing\Router;

class Application
{
    use ApplicationConfigurable;

    /**
     * @throws ReflectionException
     */
    public function __construct(string $configPath)
    {
        $this->configPath = $configPath;

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
}