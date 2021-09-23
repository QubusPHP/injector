<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2013-2014 Daniel Lowrey, Levi Morrison, Dan Ackroyd
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Injector\Cache;

use function array_key_exists;

class ArrayReflectionCache implements ReflectionCache
{
    /** @var array $cache */
    private array $cache = [];

    /**
     * Fetch a key from the cache.
     *
     * @param string $key The key to fetch.
     * @return mixed|false Value of the key in the cache, or false if not found.
     */
    public function fetch(string $key)
    {
        // The additional isset() check here improves performance but we also
        // need array_key_exists() because some cached values === NULL.
        return isset($this->cache[$key]) || array_key_exists($key, $this->cache)
        ? $this->cache[$key]
        : false;
    }

    /**
     * Store the value for a specified key in the cache.
     *
     * @param string $key  The key for which to store the value.
     * @param mixed  $data The value to store under the specified key.
     */
    public function store(string $key, $data)
    {
        $this->cache[$key] = $data;
    }
}
