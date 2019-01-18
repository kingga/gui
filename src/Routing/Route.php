<?php
/**
 * This file contains the route class.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Routing
 */

namespace Kingga\Gui\Routing;

use Kingga\Gui\HasErrors;

/**
 * The route class contains methods around creating
 * and running routes.
 */
class Route
{
    use HasErrors;

    /**
     * The ID of the route.
     *
     * @var string
     */
    private $id;

    /**
     * The class of the callback function if defined.
     *
     * @var string|null
     */
    private $class;

    /**
     * The function/method of the callback function.
     *
     * @var callable|string
     */
    private $function;

    /**
     * Create a route with a given ID.
     *
     * @param string $id The ID of the route.
     * @param string|array|callable $route The callback for the route.
     */
    public function __construct(string $id, $route)
    {
        $this->id = $id;
        $this->class = null;
        $this->function = '';

        $this->createRoute($route);
    }

    /**
     * Create the route from the callback.
     *
     * @param string|array|callable $route
     * @return void
     */
    protected function createRoute($route)
    {
        if (!is_string($route) && !is_array($route) && !is_callable($route)) {
            throw new \InvalidArgumentException('The route must be a string, array or callable.');
        }

        if (is_string($route) && !$this->validateRoute($route)) {
            throw new \InvalidArgumentException($this->getLastError());
        }

        if (is_array($route) && count($route) !== 2) {
            throw new \InvalidArgumentException('The route must contain at least 2 indexes, please use a string for a function.');
        }

        if (!is_array($route) && is_callable($route)) {
            $this->function = $route;

            return;
        }

        $class = null;
        $func = '';

        if (is_string($route)) {
            $route = trim($route);
            $exp = explode('@', $route, 2);

            if (count($exp) === 1) {
                $func = $route;
            } else {
                $class = $exp[0];
                $func = $exp[1];
            }
        } elseif (is_array($route)) {
            $route = array_values($route);
            $class = $route[0];
            $func = $route[1];
        }

        $this->class = $class;
        $this->function = $func;
    }

    /**
     * Validates a route string, e.g. Controller@methodName.
     *
     * @param string $route The route string.
     * @return boolean
     */
    protected function validateRoute(string $route): bool
    {
        $exp = explode('@', $route);
        $c = count($exp);
        if ($c > 2 || $c < 1) {
            $this->addError('The route is not valid, it must either be a function name or a @ seperated class/method.');

            return false;
        }

        return true;
    }

    /**
     * The getter for the routes ID.
     *
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * The getter for the routes class.
     *
     * @return string|null
     */
    public function getClass(): ?string
    {
        return $this->class;
    }

    /**
     * The getter for the routes function/method.
     *
     * @return string
     */
    public function getFunction(): string
    {
        return $this->function;
    }

    /**
     * The callback for the routes function/method (alias).
     *
     * @return string
     */
    public function getMethod(): string
    {
        return $this->getFunction();
    }

    /**
     * Check if a class has a namespace.
     *
     * @param string $class The name of the class.
     * @return boolean
     */
    private function hasNamespace(string $class): bool
    {
        echo $class . PHP_EOL;
        $exp = explode('\\', $class, 2);
        return count($exp) > 1;
    }

    /**
     * Add a slash to the end of the namespace if it doesn't have
     * one.
     *
     * @param string $namespace The namespace to slash (reference).
     * @return void
     */
    private function slashNamespace(string &$namespace)
    {
        // Check for end \\.
        if (substr($namespace, -1, 1) !== '\\') {
            $namespace .= '\\';
        }
    }

    /**
     * Run the route passing through a new request and the arguments.
     *
     * @param Request $request The request to pass through to the controller.
     * @param string $base_ns  The base namespace of the controllers if it hasn't been defined.
     * @return mixed
     */
    public function run(Request &$request, string $base_ns = null)
    {
        $ns = '';
        if ($base_ns && $this->class === null && $this->function && is_string($this->function) && !$this->hasNamespace($this->function)) {
            $ns = $base_ns;
            $this->slashNamespace($ns);
        }

        if ($this->class === null && is_string($this->function)) {
            return call_user_func($ns . $this->function, $request, ...$request->getArgs());
        } elseif ($this->class === null) {
            $func = $this->function;

            return $func($request, ...$request->getArgs());
        }

        $ns = '';
        if ($base_ns && $this->class && !$this->hasNamespace($this->class)) {
            $ns = $base_ns;
            $this->slashNamespace($ns);
        }

        $class = $ns . $this->class;
        return (new $class())
            ->{$this->function}($request, ...$request->getArgs());
    }
}
