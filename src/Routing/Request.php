<?php

namespace Kingga\Gui\Routing;

use Gui\Application;

class Request
{
    private $app;

    private $router;

    private $route;

    private $args;

    public function __construct(Application &$app, Router &$router, Route $route, ...$args)
    {
        $this->app = &$app;
        $this->router = &$router;
        $this->route = $route;
        $this->args = $args;
    }

    public function &getApp(): Application
    {
        return $this->app;
    }

    public function &getRouter(): Router
    {
        return $this->router;
    }

    public function getRoute(): Route
    {
        return $this->route;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function hasArg(int $index): bool
    {
        return isset($this->args[$index]);
    }

    public function getArg(int $index)
    {
        if ($this->hasArg($index)) {
            return $this->args[$index];
        }

        return null;
    }
}
