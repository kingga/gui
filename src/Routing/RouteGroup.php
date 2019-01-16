<?php

namespace Kingga\Gui\Routing;

use Kingga\Gui\HasErrors;
use Kingga\Gui\Exceptions\RouteNotFoundException;

class RouteGroup
{
    use HasErrors;

    private $groups;

    private $middlewares;

    private $routes;

    public function __construct()
    {
        $this->groups = [];
        $this->middlewares = [];
        $this->routes = [];
    }

    public function route(string $id, $route): self
    {
        $this->routes[$id] = new Route($id, $route);
        return $this;
    }

    public function group(RouteGroup $group): self
    {
        $this->groups[] = $group;
        return $this;
    }

    public function middleware(Middleware $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    public function findRoute(string $id, array $middlewares = []): \stdClass
    {
        $middlewares = array_merge($middlewares, $this->middlewares);

        // Search this groups route.
        foreach ($this->routes as $route) {
            if ($route->getId() === $id) {
                $return = ['route' => $route];

                if (!empty($middlewares)) {
                    $return['middlewares'] = $middlewares;
                }

                return (object) $return;
            }
        }

        // Search each group.
        foreach ($this->groups as $group) {
            $route = $group->findRoute($id, $middlewares);

            if (property_exists($route, 'route') && $route->route instanceof Route) {
                $return = ['route' => $route];

                if (!empty($middlewares)) {
                    $return['middlewares'] = $middlewares;
                }

                return (object) $return;
            }
        }

        throw new RouteNotFoundException("The route '$id' cannot be found.");
    }

    public function create(callable $routes)
    {
        $routes($this);
        return $this;
    }
}
