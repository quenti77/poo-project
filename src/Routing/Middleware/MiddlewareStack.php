<?php

namespace Tuto\Routing\Middleware;

use Tuto\Collections\Collection;
use Tuto\Http\Requests\Request;

class MiddlewareStack implements MiddlewareInterface
{
    /** @var Collection<int, MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares */
    private Collection $middlewares;

    public function __construct()
    {
        $this->middlewares = collect();
    }

    /**
     * @return Collection<int, MiddlewareInterface|class-string<MiddlewareInterface>>
     */
    public function getMiddlewares(): Collection
    {
        return $this->middlewares;
    }

    /**
     * @param MiddlewareInterface|class-string<MiddlewareInterface> $middleware
     * @return self
     */
    public function add(MiddlewareInterface|string $middleware): self
    {
        $this->middlewares->push($middleware);
        return $this;
    }

    /**
     * @param Collection<int, MiddlewareInterface|class-string<MiddlewareInterface>>|array<int, MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     * @return self
     */
    public function addMany(Collection|array $middlewares): self
    {
        if (is_array($middlewares)) {
            $middlewares = collect($middlewares);
        }

        foreach ($middlewares as $middleware) {
            $this->add($middleware);
        }

        return $this;
    }

    /**
     * @param Request $request
     * @param callable(Request, callable): mixed $next
     * @return mixed
     */
    public function handle(Request $request, callable $next): mixed
    {
        $pipeline = $this->middlewares
            ->reverse()
            ->reduce(
                fn (int $key, MiddlewareInterface|string $middleware, callable $stack) => $this->wrap($middleware, $stack),
                $next,
            );

        return $pipeline($request);
    }

    /**
     * @param MiddlewareInterface|class-string<MiddlewareInterface> $middleware
     * @param callable(Request, callable): mixed $next
     * @return callable
     */
    private function wrap(MiddlewareInterface|string $middleware, callable $next): callable
    {
        return static function (Request $request) use ($middleware, $next): mixed {
            if (is_string($middleware)) {
                $middleware = container()->get($middleware);
            }
            return $middleware->handle($request, $next);
        };
    }
}