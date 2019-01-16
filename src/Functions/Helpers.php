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