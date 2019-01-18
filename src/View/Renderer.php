<?php
/**
 * This file stores the Renderer class.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\View
 */

namespace Kingga\Gui\View;

use Kingga\Gui\Routing\Router;
use Sabre\Xml\Service;
use Gui\Components\Window;
use Gui\Components\Div;

/**
 * This class renders templates and also includes events. In future
 * releases, stylesheets and include statements may be included.
 * TODO: Add support for moustache templating {{ $variable }}.
 */
class Renderer
{
    /**
     * A reference to the router instance.
     *
     * @var Router
     */
    private $router;

    /**
     * The directory where the views are stored.
     *
     * @var string
     */
    private $view_dir;

    /**
     * A list of processors used for the components.
     * @see addProcessor()
     *
     * @var array
     */
    private $processors = [];

    /**
     * A list of used namespaces, defines by <use class=""> tags
     * inside of the template.
     *
     * @var array
     */
    protected $uses = [];

    /**
     * A list of style handlers for custom attributes.
     *
     * @var array
     */
    protected $styles = [];

    /**
     * A list of event handlers.
     *
     * @var array
     */
    protected $events = [];

    /**
     * Set the router instance and view directory as well as the base
     * processors, style handlers and event listeners.
     *
     * @param Router $router
     * @param string $view_dir The view directory.
     */
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

    /**
     * Use this to change the view directory or use null to set it
     * back to the default 'resources/views'.
     *
     * @param string $view_dir
     * @return void
     */
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

    /**
     * Add a tag processor to the renderer.
     *
     * @param string $class The name of the component, e.g. Gui\Components\Button::class.
     * @param callable $process The callback used to process this component.
     * @return void
     */
    protected final function addProcessor(string $class, callable $process)
    {
        if (substr($class, 0, 1) !== '\\') {
            $class = "\\$class";
        }

        $this->processors[$class] = $process;
    }

    /**
     * Add a style handler for custom attributes.
     *
     * @param string $attrib The attribute to modify, e.g. <Button custom="value">
     * @param callable $process
     * @return void
     */
    protected function addCustomStyleHandler(string $attrib, callable $process)
    {
        $this->styles[$attrib] = $process;
    }

    /**
     * Add a event listener to a custom attribute.
     *
     * @param string $event The event to bind to, e.g. <Button click="doSomething">
     * @param callable $event_handler
     * @return void
     */
    protected function addEventListener(string $event, callable $event_handler)
    {
        $this->events[$event] = $event_handler;
    }

    /**
     * Render a view.
     *
     * @param string $view The name of the view.
     * @param array $passthru The variables to pass through to the template.
     * @return void
     */
    public function render(string $view, array $passthru = [])
    {
        $service = new Service;

        $xml = file_get_contents(sprintf('%s/%s.view.xml', $this->view_dir, $view));
        $tokens = $service->parse("<view>$xml</view>");
        $this->process($tokens);

        // Cleanup.
        $this->uses = [];
    }

    /**
     * Get the full name of a process from a short name?
     * TODO: Document.
     *
     * @param string $name
     * @return void
     */
    private function getProcessName(string $name)
    {
        foreach ($this->processors as $procname => $process) {
            if (substr(strtolower($procname), -1 * strlen($name)) === strtolower($name)) {
                return $procname;
            }
        }

        return false;
    }

    /**
     * Process a node list and run styles, events and custom handlers through the
     * components.
     *
     * @param array $nodes
     * @param Application|Window|Div|mixed $wnd The parent component of the component.
     * @param string $ns The default namespace.
     * @param array $wnd_node The node of the current container.
     * @return void
     */
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

    /**
     * The tokeniser returns the name like {...}... so we
     * process it into the namespace and component name.
     *
     * @param string $name
     * @return array
     */
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

    /**
     * Get the name of the node with the namespace attached as a string.
     *
     * @param string $name
     * @return string
     */
    private function getNodeNameWithNamespace(string $name): string
    {
        return implode('\\', $this->getNodeName($name));
    }

    /**
     * Create the component with it's base settings.
     *
     * @param string $component The name of the component with namespace.
     * @param mixed ...$args The base options for the component.
     * @return Gui\Components\AbstractObject
     */
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

    /**
     * The processor for a use tag.
     *
     * @param array $node
     * @return void
     */
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

    /**
     * The processor for a window tag.
     *
     * @param array $node
     * @param string $name
     * @param mixed $wnd
     * @return void
     */
    private function pWindow(array $node, string $name, &$wnd)
    {
        $wnd = $this->createComponent($name, $node['attributes']);
        return $wnd;
    }

    /**
     * The processor for a div tag.
     *
     * @param array $node
     * @param string $name
     * @param mixed $wnd
     * @return void
     */
    private function pDiv(array $node, string $name, &$wnd)
    {
        $wnd = $this->createComponent($name, $node['attributes'], $wnd);
        return $wnd;
    }

    /**
     * The processor for all tags which don't have and processors.
     *
     * @param array $node
     * @param string $name
     * @param mixed $wnd
     * @return void
     */
    private function pUnhandled(array $node, string $name, $wnd)
    {
        if (is_string($node['value'])) {
            $node['attributes']['value'] = $node['value'];
            $node['attributes']['text'] = $node['value'];
        }

        return $this->createComponent($name, $node['attributes'], $wnd);
    }

    /**
     * Handle custom style attributes.
     *
     * @param array $node
     * @param string $name
     * @param mixed $wnd
     * @param array $wnd_node
     * @return void
     */
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

    /**
     * The style handler for align, current only supports center and the width or the parent and child
     * must be set.
     *
     * @param string $attrib
     * @param string $value
     * @param array $node
     * @param string $name
     * @param mixed $wnd
     * @param mixed $parent
     * @return void
     */
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

    /**
     * Handle all events.
     *
     * @param mixed $component
     * @param array $node
     * @param string $name
     * @param mixed $wnd
     * @param array $wnd_node
     * @return void
     */
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

    /**
     * The event handler for a click event.
     *
     * @param mixed $component
     * @param string $route
     * @param string $name
     * @param mixed $wnd
     * @param array $node
     * @param array $wnd_node
     * @return void
     */
    protected function onClick(&$component, string $route, string $name, &$wnd, array $node, array $wnd_node)
    {
        $component->on('click', function () use ($route, &$component, $name, &$wnd, $node, $wnd_node) {
            $this->router->handle($route, $component, $name, $wnd, $node, $wnd_node);
        });
    }
}