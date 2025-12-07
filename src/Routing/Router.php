<?php

namespace Tuto\Routing;

use InvalidArgumentException;
use Tuto\Collection\Collection;
use Tuto\Http\Request;

class Router
{
    /** @var Collection<string, Collection<int, Route>> $routes */
    private Collection $routes;

    /** @var Collection<string, Route> $namedRoutes */
    private Collection $namedRoutes;

    public function __construct()
    {
        $this->routes = collect();
        $this->namedRoutes = collect();
    }

    public function get(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute('GET', $path, $handler, $name);
    }

    public function post(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute('POST', $path, $handler, $name);
    }

    public function put(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute('PUT', $path, $handler, $name);
    }

    public function patch(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute('PATCH', $path, $handler, $name);
    }

    public function delete(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute('DELETE', $path, $handler, $name);
    }

    public function head(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute('HEAD', $path, $handler, $name);
    }

    public function options(string $path, callable|array $handler, string|null $name = null): Route
    {
        return $this->addRoute('OPTIONS', $path, $handler, $name);
    }

    public function addRoute(string $method, string $path, callable|array $handler, string|null $name = null): Route
    {
        $method = mb_strtoupper(trim($method));
        if (!in_array($method, Request::METHODS)) {
            throw new InvalidArgumentException("Invalid HTTP method '{$method}'");
        }

        $path = Request::trimPath($path);

        $route = new Route($method, $path, $handler, $name);
        $this->routes[$method] ??= collect();
        $this->routes[$method]->push($route);

        if ($name) {
            $this->namedRoutes[$name] = $route;
        }

        return $route;
    }

    public function match(Request $request): Route|null
    {
        if (!isset($this->routes[$request->method])) {
            return null;
        }

        return $this->routes[$request->method]->find(static fn ($key, Route $route) => $route->match($request));
    }

    /**
     * @param string $name
     * @param array<string, string> $parameters
     * @return string
     */
    public function generate(string $name, array $parameters = []): string
    {
        if (!isset($this->namedRoutes[$name])) {
            throw new InvalidArgumentException("No route found with name '{$name}'");
        }

        $generatedPath = $this->namedRoutes[$name]->generate($parameters);
        return "/{$generatedPath}";
    }
}