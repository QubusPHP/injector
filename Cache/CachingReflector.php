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

use Qubus\Injector\Reflector;
use Qubus\Injector\StandardReflector;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

use function get_class;
use function is_string;
use function strpos;
use function strtolower;
use ReflectionFunction;
use ReflectionClass;
use Closure;

class CachingReflector implements Reflector
{
    public const CACHE_KEY_CLASSES = 'injector.refls.classes.';
    public const CACHE_KEY_CTORS = 'injector.refls.ctors.';
    public const CACHE_KEY_CTOR_PARAMS = 'injector.refls.ctor-params.';
    public const CACHE_KEY_FUNCS = 'injector.refls.funcs.';
    public const CACHE_KEY_METHODS = 'injector.refls.methods.';

    private ?Reflector $reflector;

    private ?ReflectionCache $cache;

    public function __construct(?Reflector $reflector = null, ?ReflectionCache $cache = null)
    {
        $this->reflector = $reflector ?: new StandardReflector();
        $this->cache = $cache ?: new ArrayReflectionCache();
    }

    /**
     * {@inheritDoc}
     */
    public function getClass(string|object $class): ReflectionClass
    {
        $cacheKey = self::CACHE_KEY_CLASSES . strtolower($class);

        if (($reflectionClass = $this->cache->fetch($cacheKey)) === false) {
            $reflectionClass = $this->reflector->getClass($class);
            $this->cache->store($cacheKey, $reflectionClass);
        }

        return $reflectionClass;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructor(string|object $class): ?ReflectionMethod
    {
        $cacheKey = self::CACHE_KEY_CTORS . strtolower($class);

        if (($reflectedConstructor = $this->cache->fetch($cacheKey)) === false) {
            $reflectedConstructor = $this->reflector->getConstructor($class);
            $this->cache->store($cacheKey, $reflectedConstructor);
        }

        return $reflectedConstructor;
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructorParams(string|object $class)
    {
        $cacheKey = self::CACHE_KEY_CTOR_PARAMS . strtolower($class);

        if (($reflectedConstructorParams = $this->cache->fetch($cacheKey)) === false) {
            $reflectedConstructorParams = $this->reflector->getConstructorParams($class);
            $this->cache->store($cacheKey, $reflectedConstructorParams);
        }

        return $reflectedConstructorParams;
    }

    /**
     * {@inheritDoc}
     */
    public function getParamTypeHint(ReflectionFunctionAbstract $function, ReflectionParameter $param): ?string
    {
        $lowParam = strtolower($param->name);

        if ($function instanceof ReflectionMethod) {
            $lowClass = strtolower($function->class);
            $lowMethod = strtolower($function->name);
            $paramCacheKey = self::CACHE_KEY_CLASSES . "{$lowClass}.{$lowMethod}.param-{$lowParam}";
        } else {
            $lowFunc = strtolower($function->name);
            $paramCacheKey = strpos($lowFunc, '{closure}') === false
            ? self::CACHE_KEY_FUNCS . ".{$lowFunc}.param-{$lowParam}"
            : null;
        }

        $typeHint = $paramCacheKey === null ? false : $this->cache->fetch($paramCacheKey);

        if (false === $typeHint) {
            $typeHint = $this->reflector->getParamTypeHint($function, $param);
            if ($paramCacheKey !== null) {
                $this->cache->store($paramCacheKey, $typeHint);
            }
        }

        return $typeHint;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunction(string|Closure $functionName): ReflectionFunction
    {
        $lowFunc = strtolower($functionName);
        $cacheKey = self::CACHE_KEY_FUNCS . $lowFunc;

        if (($reflectedFunc = $this->cache->fetch($cacheKey)) === false) {
            $reflectedFunc = $this->reflector->getFunction($functionName);
            $this->cache->store($cacheKey, $reflectedFunc);
        }

        return $reflectedFunc;
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(string|object $classNameOrInstance, string $methodName): ReflectionMethod
    {
        $className = is_string($classNameOrInstance)
        ? $classNameOrInstance
        : get_class($classNameOrInstance);

        $cacheKey = self::CACHE_KEY_METHODS . strtolower($className) . '.' . strtolower($methodName);

        if (($reflectedMethod = $this->cache->fetch($cacheKey)) === false) {
            $reflectedMethod = $this->reflector->getMethod($classNameOrInstance, $methodName);
            $this->cache->store($cacheKey, $reflectedMethod);
        }

        return $reflectedMethod;
    }
}
