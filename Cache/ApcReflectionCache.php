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

class ApcReflectionCache implements ReflectionCache
{
    private ?ArrayReflectionCache $cache = null;

    private int $timeToLive = 5;

    /**
     * Instantiate an ApcReflectionCache object.
     */
    public function __construct(?ReflectionCache $cache = null)
    {
        $this->cache = $cache ?: new ArrayReflectionCache();
    }

    /**
     * Set the expiry time.
     *
     * @param int $seconds The number of seconds that the cache value should live.
     * @return $this Instance of the cache object.
     */
    public function setTimeToLive(int $seconds): ApcReflectionCache
    {
        $seconds = (int) $seconds;
        $this->timeToLive = $seconds > 0 ? $seconds : $this->timeToLive;

        return $this;
    }

    /**
     * Fetch a key from the cache.
     *
     * @param string $key The key to fetch.
     * @return mixed|false Value of the key in the cache, or false if not found.
     */
    public function fetch(string $key): mixed
    {
        $localData = $this->cache->fetch($key);

        if ($localData !== false) {
            return $localData;
        } else {
            $success = null; // stupid by-ref parameter that scrutinizer complains about
            $data = apc_fetch($key, $success);
            return $success ? $data : false;
        }
    }

    /**
     * Store the value for a specified key in the cache.
     *
     * @param string $key  The key for which to store the value.
     * @param mixed  $data The value to store under the specified key.
     */
    public function store(string $key, $data): void
    {
        $ret = apc_store($key, $data, $this->timeToLive);
        if ($ret === false) {
            throw new Exception();
        }

        $this->cache->store($key, $data);
    }
}
