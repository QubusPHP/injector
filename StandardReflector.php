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

namespace Qubus\Injector;

use Closure;
use ReflectionClass;
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
     */
    public function getClass(string|object $class): ReflectionClass
    {
        return new ReflectionClass($class);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructor(string|object $class): ?ReflectionMethod
    {
        $reflectionClass = new ReflectionClass($class);

        return $reflectionClass->getConstructor();
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructorParams(string|object $class)
    {
        $reflectedConstructor = $this->getConstructor($class);

        return $reflectedConstructor
        ? $reflectedConstructor->getParameters()
        : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getParamTypeHint(ReflectionFunctionAbstract $function, ReflectionParameter $param): ?string
    {
        if (PHP_VERSION_ID >= 80000) {
            $reflectionClass = $param->getType() ? (string) $param->getType() : null;
        } else {
            $reflectionClass = $param->getClass();
            if ($reflectionClass) {
                $reflectionClass = $reflectionClass->getName();
            }
        }

        return $reflectionClass ?? null;
    }

    /**
     * {@inheritDoc}
     */
    public function getFunction(string|Closure $functionName): ReflectionFunction
    {
        return new ReflectionFunction($functionName);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod(string|object $classNameOrInstance, string $methodName): ReflectionMethod
    {
        $className = is_string($classNameOrInstance)
        ? $classNameOrInstance
        : $classNameOrInstance::class;

        return new ReflectionMethod($className, $methodName);
    }
}
