<?php

namespace Kingga\Gui\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

class BuildApplication extends Command
{
    protected static $defaultName = 'build';

    protected $buildPath = 'build';

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
        
        $file = base_path("{$this->buildPath}/") . env('APP_FILE', 'app') . '.phar';

        // Cleanup.
        if (is_dir(base_path($this->buildPath))) {
            $this->cleanBuildDirectory();
        }

        mkdir(base_path($this->buildPath));

        // Create phar.
        $section = $output->section();
        $section->writeln('Creating PHAR');
        $p = new \Phar($file);

        // Add to phar.
        $p->buildFromDirectory(base_path(), '/^.*\.(php|env|xml|jpg|jpeg|png|gif|svg|json|txt|ico)$/');
        $p->setDefaultStub('app.php', '/app.php');

        $section->writeln('PHAR created');

        $section = $output->section();
        $section->writeln('Copying files.');
        $this->copyFiles(base_path(), $section);

        // $section = $output->section();
        $section->writeln('Setting permissions.');
        // copy(base_path('run.sh'), base_path($this->buildPath) . '/run.sh');
        chmod(base_path($this->buildPath) . '/run.sh', 775);

        $section->writeln('<info>Application Built</info>');
    }

    private function copyFiles(string $path, OutputInterface &$output)
    {
        $dir = new \DirectoryIterator($path);
        $exclude_directories = ['.git', $this->buildPath, 'test', 'tests', 'Tests', 'examples', 'vendor'];
        $exclude_files = ['.example.env', 'composer.json', 'composer.lock', '.gitignore', '.gitkeep', 'README.md'];
        $copy_file_types = ['sqlite', 'o', 'or', 'ppu', 'sh', 'exe', 'ico', 'lpi', 'lpr', 'lps', 'res', 'lfm', 'pas'];

        foreach ($dir as $file) {
            if ($file->isDot()) {
                continue;
            } elseif ($file->isFile()) {
                if ($file->getExtension() === 'php' || !in_array($file->getExtension(), $copy_file_types)) {
                    continue;
                }

                if (in_array($file->getFilename(), $exclude_files)) {
                    continue;
                }

                $output->writeln('Copying file: ' . $file->getFilename());
                copy($file->getPathname(), base_path($this->buildPath) . "/{$file->getFilename()}");
            } elseif ($file->isDir()) {
                if (in_array($file->getFilename(), $exclude_directories)) {
                    $output->writeln("<comment>Excluding path: {$file->getPathname()}</comment>");
                    continue;
                }

                $this->copyFiles("$path/" . $file->getFilename(), $output);
            }
        }

        // Copy certain files from the vendor directory.
        // $this->copyDirectory(base_path('vendor/gabrielrcouto/php-gui/lazarus'), base_path($this->buildPath));

        $vendor = base_path('vendor/gabrielrcouto/php-gui/lazarus');
        $dest = base_path($this->buildPath);
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            copy("$vendor/phpgui-x86_64-win64.exe", "$dest/phpgui-x86_64-win64.exe");
        } elseif (php_uname('s') === 'Linux') {
            copy("$vendor/phpgui-x86_64-linux", "$dest/phpgui-x86_64-linux");
            chmod("$dest/phpgui-x86_64-linux", 755);
        } else {
            // TODO: Check what files Mac OS X needs.
            copy("$vendor/phpgui-i386-darwin", "$dest/phpgui-i386-darwin");
            chmod("$dest/phpgui-i386-darwin", 755);
            $this->copyDirectory("$vendor/phpgui-i386-darwin.app", $dest);
        }
    }

    private function copyDirectory(string $src, string $dest)
    {
        // Check for sym links.
        // if (is_link($src)) {
        //     return symlink(readlink($src), $dest);
        // }

        // Simple copy for a file.
        if (is_file($src)) {
            return copy($src, $dest);
        }

        // Make destination directory.
        if (!is_dir($dest)) {
            mkdir($dest);
        }

        // Loop through the folder.
        $dir = dir($src);
        while (false !== $entry = $dir->read()) {
            // Skip pointers.
            if ($entry == '.' || $entry == '..') {
                continue;
            }

            // Deep copy directories.
            $this->copyDirectory("$src/$entry", "$dest/$entry");
        }

        // Clean up.
        $dir->close();
        return true;
    }

    private function cleanBuildDirectory(string $path = null)
    {
        if ($path === null) {
            $path = base_path($this->buildPath);
        }

        $dir = new \DirectoryIterator($path);

        foreach ($dir as $file) {
            if ($file->isDot()) {
                continue;
            } elseif ($file->isFile() || $file->isLink()) {
                unlink($file->getPathname());
            } elseif ($file->isDir()) {
                $this->cleanBuildDirectory($file->getPathname());
            }
        }

        rmdir($path);
    }
}
