<?php

namespace Tuto\Application;

use JsonException;
use ReflectionException;
use Tuto\Application\Loaders\ConfigurationLoader;
use Tuto\Application\Loaders\EnvironmentLoader;
use Tuto\Application\Loaders\HttpRouterLoader;
use Tuto\Application\Loaders\LoaderInterface;
use Tuto\Collections\Collection;
use Tuto\Http\Requests\Request;
use Tuto\Http\Responses\AbstractResponse;
use Tuto\Http\Responses\HttpCode;
use Tuto\Http\Responses\JsonResponse;

class HttpApplication extends BaseApplication
{
    public function __construct(private readonly Request $request)
    {
        parent::__construct();
    }

    /**
     * @return Collection<int, LoaderInterface>
     */
    protected function loaders(): Collection
    {
        return collect([
            new EnvironmentLoader(),
            new ConfigurationLoader(ROOT . '/config'),
            new HttpRouterLoader(),
        ]);
    }

    /**
     * @return void
     * @throws JsonException
     * @throws ReflectionException
     */
    public function run(): void
    {
        $route = router()->match($this->request);
        if ($route === null) {
            $this->render(json(['error' => true, 'message' => 'Not Found'], HttpCode::NOT_FOUND));
        }

        $resolver = container()->resolver();
        $context = $route->getMatches()->all();

        $response = is_array($route->getHandler())
            ? $resolver->resolveArray($route->getHandler(), $context)
            : $resolver->resolveCallable($route->getHandler(), $context);

        if (!($response instanceof AbstractResponse)) {
            $response = $this->createJsonResponse($response);
        }

        $this->render($response);
    }

    /**
     * @param AbstractResponse $response
     * @return never
     */
    private function render(AbstractResponse $response): never
    {
        request()->cookies->export();

        $response->renderHeaders();
        echo $response->getBody();
        exit;
    }

    /**
     * @param mixed $current
     * @return AbstractResponse
     * @throws JsonException
     */
    private function createJsonResponse(mixed $current): AbstractResponse
    {
        try {
            return json($current);
        } catch (JsonException) {
            return json(['error' => true, 'message' => 'Cannot be render to json'], HttpCode::INTERNAL_SERVER_ERROR);
        }
    }
}