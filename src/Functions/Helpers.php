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
        $bp = dirname(dirname(dirname(__FILE__)));

        // If the bp is the vendor folder, step back again.
        if (folder_name($bp) === 'vendor') {
            $bp = dirname($bp);
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
