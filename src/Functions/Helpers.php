<?php

if (!function_exists('env')) {
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
    function folder_name(string $path)
    {
        return pathinfo($path, PATHINFO_DIRNAME);
    }
}

if (!function_exists('base_path')) {
    function base_path(string $extended = null)
    {
        // TODO: When built into the .phar file the base directory won't work
        // as the vendor directory is inside of the phar:///... directory.

        $dirname = null;
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
    function database_path(string $extended = null)
    {
        $bp = base_path(env('PATH_DATABASE', 'database'));

        if ($extended) {
            return sprintf('%s/%s', $bp, $extended);
        }

        return $bp;
    }
}
