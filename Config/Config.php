<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Injector\Config;

interface Config
{
    /**
     * Retuns configuration value. If doesn't exist, return the set default value.
     *
     * @param string $key
     * @param mixed $default
     * @return string|array
     */
    public function get($key, $default = null);

    /**
     * Checks if key value exists.
     *
     * @param string $key
     */
    public function has($key): bool;
}
