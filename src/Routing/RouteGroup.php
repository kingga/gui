<?php
/**
 * This file contains the RouteGroup class.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Routing
 */

namespace Kingga\Gui\Routing;

use Kingga\Gui\HasErrors;
use Kingga\Gui\Exceptions\RouteNotFoundException;

/**
 * A route group stores a group of routes (like it's name says)
 * and has methods which the router can use such as findRoute(...)
 * and methods which the developer can use such as route(...)
 * and middleware(...).
 */
class RouteGroup
{
    use HasErrors;

    /**
     * The groups inside of this group.
     *
     * @var array
     */
    private $groups;

    /**
     * The middlewares to be run on this group of routes.
     *
     * @var array
     */
    private $middlewares;

    /**
     * The routes within this group.
     *
     * @var array
     */
    private $routes;

    /**
     * Initialise base properties.
     */
    public function __construct()
    {
        $this->groups = [];
        $this->middlewares = [];
        $this->routes = [];
    }

    /**
     * Create a new route.
     *
     * @param string $id The ID of the route.
     * @param string|array|callable $route The callback/controller method.
     * @return self
     */
    public function route(string $id, $route): self
    {
        $this->routes[$id] = new Route($id, $route);
        return $this;
    }

    /**
     * Create a new group inside of this one.
     *
     * @param RouteGroup $group
     * @return self
     */
    public function group(RouteGroup $group): self
    {
        $this->groups[] = $group;
        return $this;
    }

    /**
     * Add a middleware for this group to run before all routes.
     *
     * @param Middleware $middleware
     * @return self
     */
    public function middleware(Middleware $middleware): self
    {
        $this->middlewares[] = $middleware;
        return $this;
    }

    /**
     * Find a route from all groups inside of this one.
     *
     * @param string $id The ID of the route.
     * @param array $middlewares This should only be set from within the method.
     * @throws RouteNotFoundException If the route could not be found within this group.
     * @return \stdClass Two properties, route and middlewares.
     */
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

    /**
     * Create the group and add routes to it.
     *
     * @param callable $routes An instance of this group will be passed into this function.
     * @return void
     */
    public function create(callable $routes)
    {
        $routes($this);
        return $this;
    }
}
