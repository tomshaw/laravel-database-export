<?php

namespace TomShaw\DatabaseExport\Helpers;

/**
 * Class Env
 *
 * Helper class for handling environment variables.
 */
class Env
{
    /**
     * Get an environment variable.
     *
     * This method retrieves the value of an environment variable or returns a default value if the environment variable is not set.
     *
     * @param  string  $key The key of the environment variable.
     * @param  mixed  $default The default value to return if the environment variable is not set.
     * @return mixed The value of the environment variable or the default value.
     */
    public static function get($key, $default = null)
    {
        return env($key, $default);
    }

    public static function isWindows()
    {
        return (PHP_OS_FAMILY == 'Windows') ? true : false;
    }
}
