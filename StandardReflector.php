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
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionUnionType;

use function is_string;

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
        $type = $param->getType();
        $namedType = $type instanceof ReflectionNamedType ? $type : null;

        if ($type instanceof ReflectionUnionType) {
            $classTypes = [];
            foreach ($type->getTypes() as $candidate) {
                if ($candidate instanceof ReflectionNamedType && ! $candidate->isBuiltin()) {
                    $classTypes[] = $candidate;
                }
            }

            $namedType = count($classTypes) === 1 ? $classTypes[0] : null;
        }

        if ($namedType === null || $namedType->isBuiltin()) {
            return null;
        }

        $name = $namedType->getName();
        if ($function instanceof ReflectionMethod) {
            $declaringClass = $function->getDeclaringClass();
            $parentClass = $declaringClass->getParentClass();
            $name = match ($name) {
                'self', 'static' => $declaringClass->getName(),
                'parent' => $parentClass === false ? $name : $parentClass->getName(),
                default => $name,
            };
        }

        return $name;
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
