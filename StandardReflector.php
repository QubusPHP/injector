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

namespace Qubus\Injector;

use Closure;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

use function is_string;

use const PHP_VERSION_ID;

class StandardReflector implements Reflector
{
    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getClass(string|object $class): ReflectionClass
    {
        return new ReflectionClass($class);
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getConstructor(string|object $class): ?ReflectionMethod
    {
        $reflectionClass = new ReflectionClass($class);

        return $reflectionClass->getConstructor();
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getConstructorParams(string|object $class)
    {
        $reflectedConstructor = $this->getConstructor($class);

        return $reflectedConstructor?->getParameters();
    }

    /**
     * {@inheritDoc}
     */
    public function getParamTypeHint(ReflectionFunctionAbstract $function, ReflectionParameter $param): ?string
    {
        if (PHP_VERSION_ID >= 80000) {
            $reflectionClass = $param->getType() ? (string) $param->getType() : null;
        } else {
            $reflectionClass = $param->getType();
            if ($reflectionClass) {
                $reflectionClass = $reflectionClass->getName();
            }
        }

        return $reflectionClass ?? null;
    }

    /**
     * {@inheritDoc}
     * @throws ReflectionException
     */
    public function getFunction(string|Closure $functionName): ReflectionFunction
    {
        return new ReflectionFunction($functionName);
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

        return new ReflectionMethod($className, $methodName);
    }
}
