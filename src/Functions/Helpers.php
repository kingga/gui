<?php
/**
 * This file contains helper functions which are used throughout
 * the application. If this file gets to large, it will probably
 * be seperated out into seperate function files for readability.
 * 
 * @author Isaac Skelton <contact@isaacskelton.com>
 * @package \
 */

if (!function_exists('env')) {
    /**
     * A simple wrapper function around getenv. This function
     * also allows you to define a default value.
     *
     * @param string $key     The ENV key to get.
     * @param mixed  $default The default value if the key doesn't exist.
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        $val = getenv($key);
        if (!$val) {
            $val = $default;
        }

        return $val;
    }
}

/* Paths */
if (!function_exists('folder_name')) {
    /**
     * A simple wrapper around the pathinfo function which
     * retrieves the the folders name.
     *
     * @param string $path The path of the folder.
     * @return string
     */
    function folder_name(string $path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the base path of the application. This is defined as
     * the path which contains the vendor folder. This method also
     * has checks around if the application is running from within
     * a PHAR file.
     *
     * @param string $extended An extended path to add onto the base path.
     * @return string
     */
    function base_path(string $extended = null): string
    {
        // TODO: When built into the .phar file the base directory won't work
        // as the vendor directory is inside of the phar:///... directory.
        $dirname = null;
        $phar = Phar::running(false);
        if (!empty($phar)) {
            return dirname($phar);
        }

        $bp = dirname(__FILE__);
        $bp = str_replace('\\', '/', $bp);

        do {
            $bp = dirname($bp);
            $exp = explode('/', $bp);
            $dirname = $exp[count($exp) - 1];
        } while ($dirname !== 'vendor');
        $bp = dirname($bp);

        if ($bp === '/') {
            throw new \Exception('Could not find the project\'s root.');
        }

        // If an extended path has been defined append it.
        if ($extended) {
            return sprintf('%s/%s', $bp, $extended);
        }

        return $bp;
    }
}

if (!function_exists('database_path')) {
    /**
     * This method returns the path used which the database file is stored in.
     *
     * @param string $extended An extended path on top of the database path.
     * @return string
     */
    function database_path(string $extended = null): string
    {
        $bp = base_path(env('PATH_DATABASE', 'database'));

        if ($extended) {
            return sprintf('%s/%s', $bp, $extended);
        }

        return $bp;
    }
}

if (!function_exists('controller_path')) {
    /**
     * This method returns the path that the controllers are stored in.
     *
     * @param string $extended
     * @return string
     */
    function controller_path(string $extended = null): string
    {
        $bp = base_path(str_replace('\\', '/', env('NS_CONTROLLERS', 'Classes\\Controllers')));

        if ($extended) {
            return sprintf('%s/%s', $bp, $extended);
        }

        return $bp;
    }
}

if (!function_exists('model_path')) {
    /**
     * This method returns the path that the models are stored in.
     *
     * @param string $extended
     * @return string
     */
    function model_path(string $extended = null): string
    {
        $bp = base_path(str_replace('\\', '/', env('NS_MODELS', 'Classes\\Models')));

        if ($extended) {
            return sprintf('%s/%s', $bp, $extended);
        }

        return $bp;
    }
}
