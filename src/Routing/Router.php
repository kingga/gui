<?php
/**
 * This file contains the router class.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Routing
 */

namespace Kingga\Gui\Routing;

use Gui\Application;
use Kingga\Gui\HasErrors;
use Kingga\Gui\View\Renderer;

/**
 * The router handles requests from the application.
 */
class Router
{
    use HasErrors;

    /**
     * The base namespace for the controllers.
     *
     * @var string
     */
    private $base_ns;

    /**
     * The base namespace for the middleware.
     *
     * @var string
     */
    private $middleware_ns;

    /**
     * An instance to the application.
     *
     * @var Gui\Application
     */
    private $app;

    /**
     * The first route group.
     *
     * @var RouteGroup
     */
    private $group;

    /**
     * An instance to the renderer so controllers can render templates.
     *
     * @var Renderer
     */
    private $renderer;

    /**
     * Setup the router with a reference to the application and the base namespaces.
     *
     * @param Application $app
     * @param string $base_ns The base namespace to the controllers.
     * @param string $middleware_ns The base namespace for the middleware.
     */
    public function __construct(Application &$app, string $base_ns = '\\', string $middleware_ns = '\\')
    {
        $this->base_ns = $base_ns;
        $this->middleware_ns = $middleware_ns;
        $this->app = &$app;
        $this->group = new RouteGroup();
    }

    /**
     * Set the renderer, this needs to be done after the constructor as
     * the renderer needs needs an instance of the router.
     *
     * @param Renderer $renderer
     * @return void
     */
    public function setRenderer(Renderer &$renderer)
    {
        $this->renderer = $renderer;
    }

    /**
     * Cleanup.
     */
    public function __destruct()
    {
        unset($this->app);
        unset($this->renderer);
        $this->group = null;
        $this->errors = null;
    }

    /**
     * Call a method from the RouteGroup.
     *
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->group, $name)) {
            return $this->group->{$name}(...$arguments);
        }
    }

    /**
     * Create a route group.
     *
     * @param callable $routes This will be passed in the groups instance.
     * @return self
     */
    public function create(callable $routes): self
    {
        $routes($this->group);

        return $this;
    }

    /**
     * Handle a request.
     *
     * @param string $id The routes ID.
     * @param mixed  ...$args The arguments to pass into the request.
     * @return mixed
     */
    public function handle(string $id, ...$args)
    {
        try {
            // RouteNotFoundException
            $info = $this->group->findRoute($id);

            $request = new Request($this->app, $this, $info->route, $this->renderer, ...$args);

            // Run middlewares.
            if (property_exists($info, 'middlewares')) {
                foreach ($info->middlewares as $middleware) {
                    $middleware->run($request, $this->middleware_ns);
                }
            }

            // Run route.
            return $info->route->run($request, $this->base_ns);
        } catch (\Throwable $e) {
            $this->app->terminate();
            throw $e;
        }
    }
}
