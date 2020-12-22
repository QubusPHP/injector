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

use ArrayObject;
use Traversable;

use function array_key_exists;
use function array_replace_recursive;
use function explode;
use function is_int;
use function iterator_to_array;
use function json_encode;
use function strripos;
use function strval;

class InjectorConfig extends ArrayObject implements Config
{
    /** @var array $storage */
    private array $storage = [];

    /** @var array $temp */
    private array $temp = [];

    /** @var mixed */
    private $default;

    /**
     * Array key level delimiter.
     */
    private static string $delimiter = ".";

    /**
     * Config constructor
     *
     * @param array $config
     * @param array $default
     */
    public function __construct($config = [], $default = [])
    {
        $this->merge($default, $config);
        parent::__construct($this->storage, ArrayObject::ARRAY_AS_PROPS);
    }

    /**
     * {@inheritDoc}
     */
    public function all(): array
    {
        return $this->getArrayCopy();
    }

    /**
     * {@inheritDoc}
     */
    public function get($key, $default = null)
    {
        $this->default = $default;

        if (! $this->has($key)) {
            return $default;
        }

        // The class::temp variable is always setted by the class::has() method
        return $this->temp;
    }

    /**
     * {@inheritDoc}
     */
    public function has($key): bool
    {
        $this->temp = $this->search($this->storage, $key, $this->default);
        $this->default = null;
        return isset($this->temp);
    }

    /**
     * {@inheritDoc}
     */
    public function add($key, $value)
    {
        $this->storage[ $key ] = $value;
        parent::exchangeArray($this->storage);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(...$withKeys)
    {
        foreach ($withKeys as $keys) {
            foreach ((array) $keys as $k) {
                unset($this->storage[ $k ]);
            }
        }

        parent::exchangeArray($this->storage);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function merge(...$arrayToMerge)
    {
        foreach ($arrayToMerge as $key => $arr) {
            if ($arr instanceof Traversable) {
                $arr = iterator_to_array($arr);
            }
            // Make sure any value given is casting to array
            $arrayToMerge[ $key ] = (array) $arr;
        }

        // We don't need to foreach here, \array_replace_recursive() do the job for us.
        $this->storage = (array) array_replace_recursive($this->storage, ...$arrayToMerge);
        parent::exchangeArray($this->storage);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function toArray(): array
    {
        return iterator_to_array($this);
    }

    /**
     * {@inheritDoc}
     */
    public function toJson(): string
    {
        return strval(json_encode($this->toArray()));
    }

    public function __clone()
    {
        $this->storage = [];
        parent::exchangeArray($this->storage);
    }

    /**
     * @link https://github.com/balambasik/input/blob/master/src/Input.php
     *
     * @param array $array
     * @param string|int $key
     * @param mixed $default
     * @return mixed
     */
    private static function search(array $array, $key, $default = null)
    {
        if (is_int($key) || strripos($key, self::$delimiter) === false) {
            return array_key_exists($key, $array) ? $array[ $key ] : $default;
        }

        $levels = (array) explode(self::$delimiter, $key);
        foreach ($levels as $level) {
            if (! array_key_exists(strval($level), $array)) {
                return $default;
            }

            $array = $array[ $level ];
        }

        return $array ?? $default;
    }

    /**
     * {@inheritDoc}
     */
    public function count()
    {
        return parent::count();
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists($index)
    {
        return $this->has($index);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet($index)
    {
        return $this->get($index);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet($index, $newval)
    {
        $this->add($index, $newval);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset($index)
    {
        $this->remove($index);
    }
}
