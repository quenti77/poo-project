<?php

namespace Tuto\Routing;

use InvalidArgumentException;
use Tuto\Collections\Collection;
use Tuto\Http\Requests\HttpMethod;
use Tuto\Http\Requests\Request;
use Tuto\Http\Requests\Uri;
use Tuto\Middleware\HasMiddlewares;

class Router
{
    use HasMiddlewares;

    /** @var Collection<HttpMethod, Collection<int, Route>> $routes */
    private Collection $routes;

    /** @var Collection<string, Route> $namedRoutes */
    private Collection $namedRoutes;

    /** @var RouteGroupStack $groups */
    private RouteGroupStack $groups;

    public function __construct()
    {
        $this->routes = collect();
        $this->namedRoutes = collect();
        $this->groups = new RouteGroupStack();

        $this->initializeMiddlewares();
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     * @param string|null $name
     * @return Route
     */
    public function get(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute(HttpMethod::GET, $path, $handler, $name);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     * @param string|null $name
     * @return Route
     */
    public function post(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute(HttpMethod::POST, $path, $handler, $name);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     * @param string|null $name
     * @return Route
     */
    public function put(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute(HttpMethod::PUT, $path, $handler, $name);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     * @param string|null $name
     * @return Route
     */
    public function patch(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute(HttpMethod::PATCH, $path, $handler, $name);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     * @param string|null $name
     * @return Route
     */
    public function delete(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute(HttpMethod::DELETE, $path, $handler, $name);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     * @param string|null $name
     * @return Route
     */
    public function head(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute(HttpMethod::HEAD, $path, $handler, $name);
    }

    /**
     * @param string $path
     * @param callable|array{class-string, string} $handler
     * @param string|null $name
     * @return Route
     */
    public function options(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute(HttpMethod::OPTIONS, $path, $handler, $name);
    }

    /**
     * @param array{prefix?: string, name?: string, where?: array<string, string>}|RouteGroup $group
     * @param callable $callback
     * @return RouteGroup
     */
    public function group(array|RouteGroup $group, callable $callback): RouteGroup
    {
        if (is_array($group)) {
            $group = RouteGroup::fromArray($group);
        }

        $this->groups->push($group);
        $callback($this);
        $this->groups->pop();

        return $group;
    }

    /**
     * @param HttpMethod $method
     * @param string $path
     * @param callable|array{class-string, string} $handler
     * @param string|null $name
     * @return Route
     */
    public function addRoute(
        HttpMethod $method,
        string $path,
        callable|array $handler,
        string|null $name = null,
    ): Route {
        $path = Uri::trimPath($path);
        $path = Uri::trimPath($this->groups->getPrefix() . '/' . $path);
        if ($name !== null) {
            $name = $this->groups->getName() . $name;
        }

        $route = new Route($method, $path, $handler, $name);
        $route->setPathParameters($this->groups->getParameters());

        $groupMiddlewares = $this->groups->getMiddlewares();
        if (!$groupMiddlewares->isEmpty()) {
            $route->middlewares($groupMiddlewares);
        }

        $this->routes[$method->value] ??= collect();
        $this->routes[$method->value]->push($route);

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        return $route;
    }

    /**
     * @param Request $request
     * @return Route|null
     */
    public function match(Request $request): Route|null
    {
        $method = $request->method->value;
        if (!$this->routes->hasKeys($method)) {
            return null;
        }

        return $this->routes[$method]->find(static fn ($key, Route $route) => $route->match($request));
    }

    /**
     * @param string $name
     * @param array<string, string> $parameters
     * @param bool $fullUri
     * @return string
     */
    public function generate(string $name, array $parameters = [], bool $fullUri = true): string
    {
        if (!$this->namedRoutes->hasKeys($name)) {
            throw new InvalidArgumentException("No route found with name '{$name}'");
        }

        return $this->namedRoutes[$name]->generate($parameters, $fullUri);
    }
}