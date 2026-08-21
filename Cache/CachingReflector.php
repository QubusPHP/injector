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

use Closure;
use Qubus\Injector\Reflector;
use Qubus\Injector\StandardReflector;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

use function is_string;
use function strpos;
use function strtolower;

class CachingReflector implements Reflector
{
    public const CACHE_KEY_CLASSES = 'injector.refls.classes.';
    public const CACHE_KEY_CTORS = 'injector.refls.ctors.';
    public const CACHE_KEY_CTOR_PARAMS = 'injector.refls.ctor-params.';
    public const CACHE_KEY_FUNCS = 'injector.refls.funcs.';
    public const CACHE_KEY_METHODS = 'injector.refls.methods.';

    private Reflector $reflector;

    private ReflectionCache $cache;

    public function __construct(?Reflector $reflector = null, ?ReflectionCache $cache = null)
    {
        $this->reflector = $reflector ?: new StandardReflector();
        $this->cache = $cache ?: new ArrayReflectionCache();
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getClass(string|object $class): ReflectionClass
    {
        $className = is_string($class) ? $class : $class::class;
        $cacheKey = self::CACHE_KEY_CLASSES . strtolower($className);

        $reflectionClass = $this->cache->fetch($cacheKey);
        if (! $reflectionClass instanceof ReflectionClass) {
            $reflectionClass = $this->reflector->getClass($class);
            $this->cache->store($cacheKey, $reflectionClass);
        }

        return $reflectionClass;
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getConstructor(string|object $class): ?ReflectionMethod
    {
        $className = is_string($class) ? $class : $class::class;
        $cacheKey = self::CACHE_KEY_CTORS . strtolower($className);

        $reflectedConstructor = $this->cache->fetch($cacheKey);
        if ($reflectedConstructor !== null && ! $reflectedConstructor instanceof ReflectionMethod) {
            $reflectedConstructor = $this->reflector->getConstructor($class);
            $this->cache->store($cacheKey, $reflectedConstructor);
        }

        return $reflectedConstructor;
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getConstructorParams(string|object $class)
    {
        $className = is_string($class) ? $class : $class::class;
        $cacheKey = self::CACHE_KEY_CTOR_PARAMS . strtolower($className);

        $reflectedConstructorParams = $this->cache->fetch($cacheKey);
        if (! is_array($reflectedConstructorParams) && $reflectedConstructorParams !== null) {
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
            $paramCacheKey = !str_contains($lowFunc, '{closure}')
            ? self::CACHE_KEY_FUNCS . ".{$lowFunc}.param-{$lowParam}"
            : null;
        }

        $typeHint = $paramCacheKey === null ? false : $this->cache->fetch($paramCacheKey);

        if (! is_string($typeHint) && $typeHint !== null) {
            $typeHint = $this->reflector->getParamTypeHint($function, $param);
            if ($paramCacheKey !== null) {
                $this->cache->store($paramCacheKey, $typeHint);
            }
        }

        return $typeHint;
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getFunction(string|Closure $functionName): ReflectionFunction
    {
        if ($functionName instanceof Closure) {
            return $this->reflector->getFunction($functionName);
        }

        $lowFunc = strtolower($functionName);
        $cacheKey = self::CACHE_KEY_FUNCS . $lowFunc;

        $reflectedFunc = $this->cache->fetch($cacheKey);
        if (! $reflectedFunc instanceof ReflectionFunction) {
            $reflectedFunc = $this->reflector->getFunction($functionName);
            $this->cache->store($cacheKey, $reflectedFunc);
        }

        return $reflectedFunc;
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getMethod(string|object $classNameOrInstance, string $methodName): ReflectionMethod
    {
        $className = is_string($classNameOrInstance)
        ? $classNameOrInstance
        : $classNameOrInstance::class;

        $cacheKey = self::CACHE_KEY_METHODS . strtolower($className) . '.' . strtolower($methodName);

        $reflectedMethod = $this->cache->fetch($cacheKey);
        if (! $reflectedMethod instanceof ReflectionMethod) {
            $reflectedMethod = $this->reflector->getMethod($classNameOrInstance, $methodName);
            $this->cache->store($cacheKey, $reflectedMethod);
        }

        return $reflectedMethod;
    }
}
