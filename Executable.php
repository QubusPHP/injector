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
use Qubus\Exception\Data\TypeException;
use ReflectionException;
use ReflectionFunctionAbstract;
use ReflectionMethod;

use function call_user_func_array;
use function func_get_args;
use function is_object;

class Executable
{
    private ReflectionFunctionAbstract $callableReflection;

    private ?object $invocationObject = null;

    private bool $isInstanceMethod;

    /**
     * @throws TypeException
     */
    public function __construct(ReflectionFunctionAbstract $reflFunc, ?object $invocationObject = null)
    {
        if ($reflFunc instanceof ReflectionMethod) {
            $this->isInstanceMethod = ! $reflFunc->isStatic();
            $this->setMethodCallable($reflFunc, $invocationObject);
        } else {
            $this->isInstanceMethod = false;
            $this->callableReflection = $reflFunc;
        }
    }

    /**
     * @throws TypeException
     */
    private function setMethodCallable(ReflectionMethod $reflection, ?object $invocationObject = null): void
    {
        if (! $reflection->isPublic()) {
            throw new TypeException('ReflectionMethod callables must be public');
        }

        if ($reflection->isStatic()) {
            $this->callableReflection = $reflection;
        } elseif (is_object($invocationObject)) {
            $this->callableReflection = $reflection;
            $this->invocationObject = $invocationObject;
        } else {
            throw new TypeException(
                'ReflectionMethod callables must specify an invocation object'
            );
        }
    }

    /**
     * @throws ReflectionException
     */
    public function __invoke()
    {
        $args = func_get_args();
        $reflection = $this->callableReflection;

        if ($reflection instanceof ReflectionMethod) {
            return $reflection->invokeArgs($this->invocationObject, $args);
        }

        return $reflection->isClosure()
        ? $this->invokeClosure($reflection, $args)
        : $reflection->invokeArgs($args);
    }

    private function invokeClosure(ReflectionFunctionAbstract $reflection, array $args): mixed
    {
        $scope = $reflection->getClosureScopeClass();
        $closure = Closure::bind(
            $reflection->getClosure(),
            $reflection->getClosureThis(),
            $scope ? $scope->name : null
        );

        return call_user_func_array($closure ?? $reflection->getClosure(), $args);
    }

    public function getCallableReflection(): ReflectionFunctionAbstract
    {
        return $this->callableReflection;
    }

    public function getInvocationObject()
    {
        return $this->invocationObject;
    }

    public function isInstanceMethod(): bool
    {
        return $this->isInstanceMethod;
    }
}
