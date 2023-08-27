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
use ReflectionFunctionAbstract;
use ReflectionMethod;

use function call_user_func_array;
use function func_get_args;
use function is_object;
use function version_compare;

use const PHP_VERSION;

class Executable
{
    /** @var ReflectionFunctionAbstract $callableReflection */
    private ReflectionFunctionAbstract $callableReflection;

    /** @var mixed $invocationObject */
    private $invocationObject;

    private bool $isInstanceMethod;

    /**
     * @throws TypeException
     */
    public function __construct(ReflectionFunctionAbstract $reflFunc, ?object $invocationObject = null)
    {
        if ($reflFunc instanceof ReflectionMethod) {
            $this->isInstanceMethod = true;
            $this->setMethodCallable($reflFunc, $invocationObject);
        } else {
            $this->isInstanceMethod = false;
            $this->callableReflection = $reflFunc;
        }
    }

    /**
     * @throws TypeException
     */
    private function setMethodCallable(ReflectionMethod $reflection, ?object $invocationObject): void
    {
        if (is_object($invocationObject)) {
            $this->callableReflection = $reflection;
            $this->invocationObject = $invocationObject;
        } elseif ($reflection->isStatic()) {
            $this->callableReflection = $reflection;
        } else {
            throw new TypeException(
                'ReflectionMethod callables must specify an invocation object'
            );
        }
    }

    public function __invoke()
    {
        $args = func_get_args();
        $reflection = $this->callableReflection;

        if ($this->isInstanceMethod) {
            return $reflection->invokeArgs($this->invocationObject, $args);
        }

        return $this->callableReflection->isClosure()
        ? $this->invokeClosureCompat($reflection, $args)
        : $reflection->invokeArgs($args);
    }

    /**
     * @todo Remove this extra indirection when 5.3 support is dropped
     */
    private function invokeClosureCompat($reflection, $args)
    {
        if (version_compare(PHP_VERSION, '5.4.0') >= 0) {
            $scope = $reflection->getClosureScopeClass();
            $closure = Closure::bind(
                $reflection->getClosure(),
                $reflection->getClosureThis(),
                $scope ? $scope->name : null
            );
            return call_user_func_array($closure, $args);
        } else {
            return $reflection->invokeArgs($args);
        }
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
