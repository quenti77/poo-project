<?php

namespace Tuto\Application;

use JsonException;
use Tuto\Application\Loaders\ConfigurationLoader;
use Tuto\Application\Loaders\EnvironmentLoader;
use Tuto\Application\Loaders\ErrorHandlerLoader;
use Tuto\Application\Loaders\HttpRouterLoader;
use Tuto\Application\Loaders\LoaderInterface;
use Tuto\Application\Loaders\ServiceProviderLoader;
use Tuto\Collections\Collection;
use Tuto\Http\Exceptions\HttpNotFoundException;
use Tuto\Http\Requests\Request;
use Tuto\Http\Responses\AbstractResponse;
use Tuto\Http\Responses\HttpCode;
use Tuto\Middleware\MiddlewareStack;

class HttpApplication extends BaseApplication
{
    /**
     * @param Request $request
     */
    public function __construct(private readonly Request $request)
    {
    }

    /**
     * @return Collection<int, LoaderInterface>
     */
    protected function loaders(): Collection
    {
        return collect([
            new EnvironmentLoader(),
            new ConfigurationLoader(ROOT . '/config'),
            new ErrorHandlerLoader(),
            new ServiceProviderLoader(),
            new HttpRouterLoader(),
        ]);
    }

    /**
     * @return void
     */
    public function run(): void
    {
        $route = router()->match($this->request);

        $stack = new MiddlewareStack();
        $stack->addMany(router()->getMiddlewareStack()->getMiddlewares());

        if ($route) {
            $this->request->updateCurrentRoute($route);
            $stack->addMany($route->getMiddlewareStack()->getMiddlewares());
        }

        $finalHandler = function (Request $request) use($route): mixed {
            if ($route === null) {
                throw new HttpNotFoundException($this->request);
            }

            $resolver = container()->resolver();
            $context = $route->getMatches()->all();

            $response = is_array($route->getHandler())
                ? $resolver->resolveArray($route->getHandler(), $context)
                : $resolver->resolveCallable($route->getHandler(), $context);

            if (!($response instanceof AbstractResponse)) {
                $response = $this->createJsonResponse($response);
            }

            return $response;
        };

        $response = $stack->handle($this->request, $finalHandler);
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
