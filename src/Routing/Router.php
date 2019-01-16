<?php

namespace Kingga\Gui\Routing;

use Gui\Application;
use Kingga\Gui\HasErrors;

class Router
{
    use HasErrors;

    private $base_ns;

    private $middleware_ns;

    private $app;

    private $group;

    public function __construct(Application &$app, string $base_ns = '\\', string $middleware_ns = '\\')
    {
        $this->base_ns = $base_ns;
        $this->middleware_ns = $middleware_ns;
        $this->app = &$app;
        $this->group = new RouteGroup();
    }

    public function __destruct()
    {
        $this->group = null;
        $this->errors = null;
    }

    public function __call(string $name, array $arguments)
    {
        if (method_exists($this->group, $name)) {
            $this->group->{$name}(...$arguments);
        }
    }

    public function create(callable $routes)
    {
        $routes($this->group);

        return $this;
    }

    public function handle(string $id, ...$args)
    {
        try {
            // RouteNotFoundException
            $info = $this->group->findRoute($id);

            $request = new Request($this->app, $this, $info->route, ...$args);

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
