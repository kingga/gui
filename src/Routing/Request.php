<?php
/**
 * This file contains the Request class.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Routing
 */

namespace Kingga\Gui\Routing;

use Gui\Application;
use Kingga\Gui\View\Renderer;

/**
 * The request class is used to hold helpful classes for the
 * controller to use along with the data send along with the
 * request.
 */
class Request
{
    /**
     * The applications instance.
     *
     * @var Gui\Application
     */
    private $app;

    /**
     * The routers instance.
     *
     * @var Kingga\Gui\Routing\Router
     */
    private $router;

    /**
     * The route which was called.
     *
     * @var Kingga\Gui\Routing\Route
     */
    private $route;

    /**
     * The renderer instance so templates can be rendered
     * from within the controller.
     *
     * @var Kingga\Gui\View\Renderer
     */
    private $renderer;

    /**
     * The arguments which were sent through with the request.
     *
     * @var array
     */
    private $args;

    public function __construct(Application &$app, Router &$router, Route $route, Renderer &$renderer, ...$args)
    {
        $this->app = &$app;
        $this->router = &$router;
        $this->route = $route;
        $this->renderer = &$renderer;
        $this->args = $args;
    }

    /**
     * Returns an instance of the application.
     *
     * @return Application
     */
    public function &getApp(): Application
    {
        return $this->app;
    }

    /**
     * Returns an instance of the router.
     *
     * @return Router
     */
    public function &getRouter(): Router
    {
        return $this->router;
    }

    /**
     * Returns an instance of the renderer.
     *
     * @return Renderer
     */
    public function &getRenderer(): Renderer
    {
        return $this->renderer;
    }

    /**
     * Returns the route used for this request.
     *
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }

    /**
     * Returns all the arguments supplied to this request.
     *
     * @return array
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * Checks if an argument was supplied.
     *
     * @param integer $index The index of the argument.
     * @return boolean
     */
    public function hasArg(int $index): bool
    {
        return isset($this->args[$index]);
    }

    /**
     * Get an argument at the given index.
     *
     * @param integer $index The argument at 'x' index.
     * @return mixed
     */
    public function getArg(int $index)
    {
        if ($this->hasArg($index)) {
            return $this->args[$index];
        }

        return null;
    }
}
