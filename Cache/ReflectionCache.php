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

namespace Qubus\Injector\Cache;

interface ReflectionCache
{
    /**
     * Fetch a key from the cache.
     *
     * @param string $key The key to fetch.
     * @return mixed|false Value of the key in the cache, or false if not found.
     */
    public function fetch(string $key);

    /**
     * Store the value for a specified key in the cache.
     *
     * @param string $key  The key for which to store the value.
     * @param mixed  $data The value to store under the specified key.
     */
    public function store(string $key, $data);
}
