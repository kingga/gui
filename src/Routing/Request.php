<?php

namespace Kingga\Gui\Routing;

use Gui\Application;
use Kingga\Gui\View\Renderer;

class Request
{
    private $app;

    private $router;

    private $route;

    private $renderer;

    private $args;

    public function __construct(Application &$app, Router &$router, Route $route, Renderer &$renderer, ...$args)
    {
        $this->app = &$app;
        $this->router = &$router;
        $this->route = $route;
        $this->renderer = &$renderer;
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

    public function &getRenderer(): Renderer
    {
        return $this->renderer;
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
