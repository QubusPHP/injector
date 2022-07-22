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
    public function get(string $key, $default = null): string|array
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
    public function has(string $key): bool
    {
        $this->temp = $this->search($this->storage, $key, $this->default);
        $this->default = null;
        return isset($this->temp);
    }

    /**
     * {@inheritDoc}
     */
    public function add($key, $value): InjectorConfig
    {
        $this->storage[ $key ] = $value;
        parent::exchangeArray($this->storage);
        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function remove(...$withKeys): InjectorConfig
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
    public function merge(...$arrayToMerge): InjectorConfig
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
     * @param array $array
     * @param mixed $default
     * @return mixed
     */
    private static function search(array $array, string|int $key, $default = null)
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
    public function count(): int
    {
        return parent::count();
    }

    /**
     * {@inheritDoc}
     */
    public function offsetExists(mixed $index): bool
    {
        return $this->has($index);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetGet(mixed $index): mixed
    {
        return $this->get($index);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetSet(mixed $index, mixed $newval): void
    {
        $this->add($index, $newval);
    }

    /**
     * {@inheritDoc}
     */
    public function offsetUnset(mixed $index): void
    {
        $this->remove($index);
    }
}
