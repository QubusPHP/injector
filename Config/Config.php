<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2020 Joshua Parker <joshua@joshuaparker.dev>
 * @copyright  2013-2014 Daniel Lowrey, Levi Morrison, Dan Ackroyd
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Injector\Config;

interface Config
{
    /**
     * Returns configuration value. If doesn't exist, return the set default value.
     *
     * @param string $key
     * @param mixed $default
     * @return string|array
     */
    public function get(string $key, mixed $default = null): string|array;

    /**
     * Checks if key value exists.
     */
    public function has(string $key): bool;
}
