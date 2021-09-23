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
use Closure;

interface Reflector
{
    /**
     * Retrieves ReflectionClass instances, caching them for future retrieval.
     *
     * @param string|object $class Class name to retrieve the ReflectionClass from.
     * @return ReflectionClass ReflectionClass object for the specified class.
     */
    public function getClass(string|object $class): ReflectionClass;

    /**
     * Retrieves and caches the constructor (ReflectionMethod) for the specified class.
     *
     * @param string|object $class Class name to retrieve the constructor from.
     * @return ReflectionMethod|null ReflectionMethod for the constructor of the specified class.
     */
    public function getConstructor(string|object $class): ?ReflectionMethod;

    /**
     * Retrieves and caches an array of constructor parameters for the given class
     *
     * @param string|object $class Class name to retrieve the constructor arguments from.
     * @return ReflectionParameter[]|null Array of ReflectionParameter objects for the given class' constructor.
     */
    public function getConstructorParams(string|object $class);

    /**
     * Retrieves the class type-hint from a given ReflectionParameter.
     *
     * There is no way to directly access a parameter's type-hint without
     * instantiating a new ReflectionClass instance and calling its getName()
     * method. This method stores the results of this approach so that if
     * the same parameter type-hint or ReflectionClass is needed again we
     * already have it cached.
     *
     * @param ReflectionFunctionAbstract $function Reflection object for the function.
     * @param ReflectionParameter        $param    Reflection object for the parameter.
     * @return string|null Type-hint of the class. Null if none available.
     */
    public function getParamTypeHint(ReflectionFunctionAbstract $function, ReflectionParameter $param): ?string;

    /**
     * Retrieves and caches a reflection for the specified function
     *
     * @param string|Closure $functionName Name of the function to get a reflection for.
     * @return ReflectionFunction ReflectionFunction object for the specified function.
     */
    public function getFunction(string|Closure $functionName): ReflectionFunction;

    /**
     * Retrieves and caches a reflection for the specified class method
     *
     * @param string|object $classNameOrInstance Class name or instance the method is referring to.
     * @param string        $methodName          Name of the method to get the reflection for.
     * @return ReflectionMethod ReflectionMethod object for the specified method.
     */
    public function getMethod(string|object $classNameOrInstance, string $methodName): ReflectionMethod;
}
