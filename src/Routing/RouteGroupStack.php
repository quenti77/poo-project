<?php

namespace Tuto\Routing;

use Tuto\Collections\Collection;
use Tuto\Middleware\MiddlewareInterface;

class RouteGroupStack
{
    /** @var Collection<int, RouteGroup> $stack */
    private Collection $stack;

    public function __construct()
    {
        $this->stack = collect();
    }

    public function push(RouteGroup $group): void
    {
        $this->stack->push($group);
    }

    public function pop(): void
    {
        $this->stack->pop();
    }

    /**
     * @return string
     */
    public function getPrefix(): string
    {
        return $this->stack
            ->filter(static fn(int $index, RouteGroup $group) => !empty($group->getPrefix()))
            ->map(static fn(int $index, RouteGroup $group) => $group->getPrefix())
            ->join('/');
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->stack
            ->map(static fn(int $index, RouteGroup $group) => $group->getName())
            ->join();
    }

    /**
     * @return Collection<string, string>
     */
    public function getParameters(): Collection
    {
        $parameters = collect();
        foreach ($this->stack as $group) {
            $parameters = $parameters->merge($group->getWhere());
        }
        return $parameters;
    }

    /**
     * @return Collection<int, MiddlewareInterface|class-string<MiddlewareInterface>>
     */
    public function getMiddlewares(): Collection
    {
        $middlewares = collect();
        foreach ($this->stack as $group) {
            $middlewares = $middlewares->merge($group->getMiddlewares());
        }
        return $middlewares;
    }
}
