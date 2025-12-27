<?php

namespace Tuto\Routing\Middleware;

use Tuto\Collections\Collection;

trait HasMiddlewares
{
    private MiddlewareStack $middlewares;

    /**
     * @return void
     */
    private function initializeMiddlewares(): void
    {
        $this->middlewares = new MiddlewareStack();
    }

    /**
     * @param MiddlewareInterface|class-string<MiddlewareInterface> $middleware
     * @return self
     */
    public function middleware(MiddlewareInterface|string $middleware): self
    {
        $this->middlewares->add($middleware);
        return $this;
    }

    /**
     * @param Collection<int, MiddlewareInterface|class-string<MiddlewareInterface>>|array<MiddlewareInterface|class-string<MiddlewareInterface>> $middlewares
     * @return self
     */
    public function middlewares(Collection|array $middlewares): self
    {
        $this->middlewares->addMany($middlewares);
        return $this;
    }

    /**
     * @return MiddlewareStack
     */
    public function getMiddlewareStack(): MiddlewareStack
    {
        return $this->middlewares;
    }
}
