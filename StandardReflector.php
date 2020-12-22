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

namespace Qubus\Injector;

use ReflectionClass;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;

use function get_class;
use function is_string;

use const PHP_VERSION_ID;

class StandardReflector implements Reflector
{
    /**
     * {@inheritDoc}
     */
    public function getClass($class)
    {
        return new ReflectionClass($class);
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructor($class)
    {
        $reflectionClass = new ReflectionClass($class);

        return $reflectionClass->getConstructor();
    }

    /**
     * {@inheritDoc}
     */
    public function getConstructorParams($class)
    {
        $reflectedConstructor = $this->getConstructor($class);

        return $reflectedConstructor
        ? $reflectedConstructor->getParameters()
        : null;
    }

    /**
     * {@inheritDoc}
     */
    public function getParamTypeHint(ReflectionFunctionAbstract $function, ReflectionParameter $param)
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
    public function getFunction($functionName)
    {
        return new ReflectionFunction($functionName);
    }

    /**
     * {@inheritDoc}
     */
    public function getMethod($classNameOrInstance, $methodName)
    {
        $className = is_string($classNameOrInstance)
        ? $classNameOrInstance
        : get_class($classNameOrInstance);

        return new ReflectionMethod($className, $methodName);
    }
}
