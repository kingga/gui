<?php
/**
 * This file contains the make command for the gtool.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Commands
 */

namespace Kingga\Gui\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Question\ConfirmationQuestion;

/**
 * This class defines the functionality around building templates
 * such as a Controller or Model.
 */
class MakeCommand extends Command
{
    /** {@inheritDoc} */
    protected static $defaultName = 'make';

    /**
     * The callbacks for each type of make, e.g. controller => mController.
     *
     * @var array
     */
    private $makeCallbacks = [];

    /**
     * Setup make callbacks.
     */
    public function __construct()
    {
        parent::__construct();
        $this->addMakeCallback('controller', [$this, 'mController']);
        $this->addMakeCallback('model', [$this, 'mModel']);
    }

    /**
     * Add a callback for a make command.
     *
     * @param callable $callback
     * @return self
     */
    protected function addMakeCallback(string $make, callable $callback)
    {
        $this->makeCallbacks[strtolower($make)] = $callback;
        return $this;
    }

    /** {@inheritDoc} */
    public function configure()
    {
        $this->setDescription('Make a template file for a Model, Controller, etc.')
            ->setHelp('Make a template file for a Model, Controller, etc. \'php gtool make controller ControllerName\'.')
            ->addArgument('type', InputArgument::REQUIRED, 'The type of template, e.g. controller, model, ...')
            ->addArgument('name', InputArgument::REQUIRED, 'The output name of the template.')
            ->addArgument('description', InputArgument::OPTIONAL, 'The classes long description.');
    }

    /** {@inheritDoc} */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = strtolower($input->getArgument('type'));
        $name = $input->getArgument('name');

        $description = '';
        if ($input->hasArgument('description') && $input->getArgument('description')) {
            $description = $input->getArgument('description');
            $description = "\n *\n * $description";
        }

        if (isset($this->makeCallbacks[$type])) {
            $this->makeCallbacks[$type]($name, $description, $input, $output);
        } else {
            $output->writeln("<error>The type '$type' has no template!</error>");
        }

        $output->writeln("<info> The $type has been created.</info>");
    }

    /**
     * Replace placeholders with their values.
     *
     * @param string $file
     * @param string $class The class name.
     * @param string $desc  The long description.
     * @return string The files content after it has been built.
     */
    protected function buildTemplate(string $file, string $class, string $desc): string
    {
        $file = file_get_contents($file);

        // Class.
        $file = str_replace('%ClassName%', $class, $file);
        
        // Description.
        $file = str_replace('%Description%', $desc, $file);

        // Date.
        $file = str_replace('%Date%', (new \DateTime)->format('d/m/Y'), $file);

        return $file;
    }

    /**
     * Check if a file is going to be replaced and ask the user first.
     *
     * @param string $file
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function doOverrite(string $file, InputInterface $input, OutputInterface $output)
    {
        if (file_exists($file)) {
            $helper = $this->getHelper('question');
            $question = new ConfirmationQuestion("Do you want to overrite the existing file [y/n]?\n", false);

            if (!$helper->ask($input, $output, $question)) {
                die;
            }
        }
    }

    /**
     * Make a controller.
     *
     * @param string $value
     * @param string $desc
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function mController(string $value, string $desc, InputInterface $input, OutputInterface $output)
    {
        $file = sprintf('%s/Templates/Controller.php', dirname(dirname(__FILE__)));
        $out = controller_path("$value.php");
        $this->doOverrite($out, $input, $output);

        $template = $this->buildTemplate($file, $value, $desc);
        file_put_contents($out, $template);
    }

    /**
     * Make a model.
     *
     * @param string $value
     * @param string $desc
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    private function mModel(string $value, string $desc, InputInterface $input, OutputInterface $output)
    {
        $file = sprintf('%s/Templates/Model.php', dirname(dirname(__FILE__)));
        $out = model_path("$value.php");
        $this->doOverrite($out, $input, $output);

        $template = $this->buildTemplate($file, $value, $desc);
        file_put_contents($out, $template);
    }
}