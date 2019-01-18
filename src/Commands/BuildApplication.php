<?php

namespace Kingga\Gui\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class BuildApplication extends Command
{
    protected static $defaultName = 'build';

    protected function configure()
    {
        $this->setDescription('Build the application into a .phar file.')
            ->setHelp('Build the application into a .phar file and create all of the assets for production.');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->section();
        $output->writeln('Building Application');
        $output->section();
        
        $file = base_path('build/') . env('APP_FILE', 'app') . '.phar';

        // Cleanup.
        if (is_dir('build')) {
            $build_dir = new \DirectoryIterator(base_path('build'));
            foreach ($build_dir as $fileInfo) {
                if ($fileInfo->isDot()) {
                    continue;
                } else {
                    unlink($fileInfo->getPathname());
                }
            }

            rmdir('build');
        }

        mkdir(base_path('build'));

        if (file_exists($file)) {
            unlink($file);
        }

        if (file_exists("$file.gz")) {
            unlink("$file.gz");
        }

        // Create phar.
        $output->writeln('Creating PHAR');
        $p = new \Phar($file);

        // Add to phar.
        // $p->buildFromDirectory(base_path(), '/^.*.php$/');

        $this->iterateDirectory(base_path(), $p, $output);

        $p->setDefaultStub('app.php', '/app.php');
        $output->writeln('PHAR created');
        $output->section();
    }

    private function iterateDirectory(string $path, \Phar &$p, OutputInterface &$output)
    {
        $dir = new \DirectoryIterator($path);
        $exclude_directories = ['.git', 'build', 'test', 'tests', 'Tests', 'examples'];

        foreach ($dir as $file) {
            if ($file->isDot()) {
                continue;
            } elseif ($file->isFile()) {
                // if ($file->getExtension() !== 'php') {
                //     continue;
                // }

                // $output->writeln('Adding file: ' . $file->getPathname());
                $p->addFile($file->getPathname());
            } elseif ($file->isDir()) {
                if (in_array($file->getFilename(), $exclude_directories)) {
                    $output->writeln("<warn>Excluding path: {$file->getPathname()}</warn>");
                    continue;
                }

                $this->iterateDirectory("$path/" . $file->getFilename(), $p, $output);
            }
        }
    }
}
