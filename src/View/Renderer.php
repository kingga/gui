<?php

namespace Kingga\Gui\View;

use Kingga\Gui\Routing\Router;
use Sabre\Xml\Service;
use Gui\Components\Window;
use Gui\Components\Div;

class Renderer
{
    private $router;

    private $view_dir;

    private $processors = [];

    protected $uses = [];

    protected $styles = [];

    protected $events = [];

    public function __construct(Router &$router, string $view_dir = null)
    {
        $this->router = &$router;

        $this->setViewDirectory($view_dir);

        $this->addProcessor('use', [$this, 'pUse']);
        $this->addProcessor(Window::class, [$this, 'pWindow']);
        $this->addProcessor(Div::class, [$this, 'pDiv']);

        $this->addCustomStyleHandler('align', [$this, 'sAlign']);

        $this->addEventListener('onclick', [$this, 'onClick']);
    }

    public function setViewDirectory(string $view_dir = null)
    {
        if (!$view_dir) {
            $view_dir = base_path('resources/views');
        }

        if (substr($view_dir, -1, 1) === '\\') {
            $view_dir = substr($view_dir, 0, strlen($view_dir) - 1);
        }

        $this->view_dir = $view_dir;
    }

    protected final function addProcessor(string $class, callable $process)
    {
        if (substr($class, 0, 1) !== '\\') {
            $class = "\\$class";
        }

        $this->processors[$class] = $process;
    }

    protected function addCustomStyleHandler(string $attrib, callable $process)
    {
        $this->styles[$attrib] = $process;
    }

    protected function addEventListener(string $event, callable $event_handler)
    {
        $this->events[$event] = $event_handler;
    }

    public function render(string $view, array $passthru = [])
    {
        $service = new Service;

        $xml = file_get_contents(sprintf('%s/%s.view.xml', $this->view_dir, $view));
        $tokens = $service->parse("<view>$xml</view>");
        $this->process($tokens);

        // Cleanup.
        $this->uses = [];
    }

    private function getProcessName(string $name)
    {
        foreach ($this->processors as $procname => $process) {
            if (substr(strtolower($procname), -1 * strlen($name)) === strtolower($name)) {
                return $procname;
            }
        }

        return false;
    }

    private function process(array $nodes, $wnd = null, $ns = '\\', $wnd_node = null)
    {
        if (isset($nodes['name'])) {
            // Get the full name with namespace (or default namespace).
            $name = $this->getNodeName($nodes['name']);
            if (empty($name[0])) {
                $name[0] = $ns;
                $name = implode('', $name);
            } else {
                $name = implode('\\', $name);
            }

            // Handle custom styles.
            $this->handleCustomStyling($nodes, $name, $wnd, $wnd_node);

            // Check for a process and call it if it exists.
            $pname = $this->getProcessname($name);
            $oldwnd = $wnd;
            if ($pname !== false) {
                $component = $this->processors[$pname]($nodes, $name, $wnd);
            } else {
                $component = $this->pUnhandled($nodes, $name, $wnd);
                // throw new \Exception("A process for the node '$name' has not been created.");
            }

            if ($component) {
                $this->handleEvents($component, $nodes, $name, $wnd, $wnd_node);
            }

            if ($oldwnd !== $wnd) {
                $wnd_node = $nodes;
            }

            // If the nodes value is an array then it has some children elements.
            if (is_array($nodes['value'])) {
                $nodes = $nodes['value'];
            }
        }

        foreach ($nodes as $node) {
            if (is_array($node)) {
                $this->process($node, $wnd, $ns, $wnd_node);
            }
        }
    }

    private function getNodeName(string $name): array
    {
        preg_match('/^{(.*)}(.*)$/', $name, $matches);
        if (count($matches) !== 3) {
            throw new \Exception("The node '$name' does not have a valid name.");
        }

        $ns = $matches[1];
        $el = $matches[2];

        return [$ns, $el];
    }

    private function getNodeNameWithNamespace(string $name): string
    {
        return implode('\\', $this->getNodeName($name));
    }

    protected function createComponent(string $component, ...$args)
    {
        $pname = $this->getProcessName($component);
        if ($pname) {
            $component = $pname;
        } else {
            // Look for uses.
            foreach ($this->uses as $use) {
                if (substr(strtolower($use), -1 * strlen($component)) === strtolower($component)) {
                    $component = $use;
                    break;
                }
            }
        }

        return new $component(...$args);
    }

    private function pUse(array $node)
    {
        if (!isset($node['attributes']['class'])) {
            throw new \Exception('The use tag must always include a class attribute.');
        }

        // Add a base namespace to it.
        $class = $node['attributes']['class'];
        if (substr($class, 0, 1) !== '\\') {
            $class = "\\$class";
        }

        $this->uses[] = $class;
    }

    private function pWindow(array $node, string $name, &$wnd)
    {
        $wnd = $this->createComponent($name, $node['attributes']);
        return $wnd;
    }

    private function pDiv(array $node, string $name, &$wnd)
    {
        $wnd = $this->createComponent($name, $node['attributes'], $wnd);
        return $wnd;
    }

    private function pUnhandled(array $node, string $name, $wnd)
    {
        if (is_string($node['value'])) {
            $node['attributes']['value'] = $node['value'];
            $node['attributes']['text'] = $node['value'];
        }

        return $this->createComponent($name, $node['attributes'], $wnd);
    }

    private function handleCustomStyling(array &$node, string $name, $wnd, array $wnd_node = null)
    {
        foreach ($this->styles as $style => $handler) {
            foreach ($node['attributes'] as $attrib => $value) {

                if (strtolower($style) === strtolower($attrib)) {
                    $handler($attrib, $value, $node, $name, $wnd, $wnd_node);
                    break;
                }
            }
        }
    }

    private function sAlign(string $attrib, string $value, array &$node, string $name, $wnd, $parent = null)
    {
        switch (strtolower($value)) {
            case 'center':
                $wnd_w = $parent ? $parent['attributes']['width'] ?? null : null;
                $com_w = (int) $node['attributes']['width'] ?? null;
                if ($com_w === null || $wnd_w === null) {
                    trigger_error('The width attribute must be set when using align:center for both the parent and node.', E_USER_WARNING);
                }

                $x = ($wnd_w / 2) - ($com_w / 2);
                $node['attributes']['left'] = $x;
                break;
            default:
                break;
        }
    }

    private function handleEvents(&$component, array $node, string $name, &$wnd, array $wnd_node = null)
    {
        foreach ($this->events as $event => $handler) {
            foreach ($node['attributes'] as $attrib => $value) {
                if (strtolower($event) === strtolower($attrib)) {
                    $handler($component, $value, $name, $wnd, $node, $wnd_node);
                }
            }
        }
    }


    protected function onClick(&$component, string $route, string $name, &$wnd, array $node, array $wnd_node)
    {
        $component->on('click', function () use ($route, &$component, $name, &$wnd, $node, $wnd_node) {
            $this->router->handle($route, $component, $name, $wnd, $node, $wnd_node);
        });
    }
}