<?php

namespace Kingga\Gui\Routing;

use Kingga\Gui\HasErrors;

class Route
{
    use HasErrors;

    private $id;

    private $class;

    private $function;

    public function __construct(string $id, $route)
    {
        $this->id = $id;
        $this->class = null;
        $this->function = '';

        $this->createRoute($route);
    }

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

    public function getId(): string
    {
        return $this->id;
    }

    public function getClass(): ?string
    {
        return $this->class;
    }

    public function getFunction(): string
    {
        return $this->function;
    }

    public function getMethod(): string
    {
        return $this->function;
    }

    private function hasNamespace(string $class): bool
    {
        echo $class . PHP_EOL;
        $exp = explode('\\', $class, 2);
        return count($exp) > 1;
    }

    private function slashNamespace(string &$namespace)
    {
        // Check for end \\.
        if (substr($namespace, -1, 1) !== '\\') {
            $namespace .= '\\';
        }
    }

    public function run(Request &$request, string $base_ns = null)
    {
        $ns = '';
        if ($base_ns && $this->class === null && $this->function && is_string($this->function) && !$this->hasNamespace($this->function)) {
            $ns = $base_ns;
            $this->slashNamespace($ns);
        }

        if ($this->class === null && is_string($this->function)) {
            return call_user_func($ns . $this->function, $request);
        } elseif ($this->class === null) {
            $func = $this->function;

            return $func($request);
        }

        $ns = '';
        if ($base_ns && $this->class && !$this->hasNamespace($this->class)) {
            $ns = $base_ns;
            $this->slashNamespace($ns);
        }

        $class = $ns . $this->class;
        return (new $class())
            ->{$this->function}($request);
    }
}