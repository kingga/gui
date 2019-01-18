<?php
/**
 * This file contains the build command for the gtool.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package Kingga\Gui\Commands
 */

namespace Kingga\Gui\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputArgument;

/**
 * This command moves all of the files for this application into a
 * PHAR file and copies over any dependencies which are required to
 * run this application such as the lazurus files and run.sh script.
 */
class BuildApplication extends Command
{
    /** {@inheritDoc} */
    protected static $defaultName = 'build';

    /**
     * The build path of the PHAR file from the root directory.
     *
     * @var string
     */
    protected $buildPath = 'build';

    /** {@inheritDoc} */
    protected function configure()
    {
        $this->setDescription('Build the application into a .phar file.')
            ->setHelp('Build the application into a .phar file and create all of the assets for production.');
    }

    /** {@inheritDoc} */
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

    /**
     * Recursively copy files from allowed directories and file formats into the
     * build path.
     *
     * @param string $path The path to copy.
     * @param OutputInterface $output The output device to write to the console.
     * @return void
     */
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

    /**
     * Copy an entire directory including sub directories to a destination.
     *
     * @param string $src  The source path.
     * @param string $dest The destination path.
     * @return void
     */
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

    /**
     * Remove all files from the build directory and then remove the build
     * directory, this should be used before building the application.
     *
     * @param string $path This should not be set outside of this method.
     * @return void
     */
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
