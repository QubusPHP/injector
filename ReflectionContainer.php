<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2021 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2013-2014 Daniel Lowrey, Levi Morrison, Dan Ackroyd
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Injector;

interface ReflectionContainer
{
    /**
     * Define instantiation directives for the specified class
     *
     * @param string $name The class (or alias) whose constructor arguments we wish to define
     * @param array  $args An array mapping parameter names to values/instructions
     * @return ReflectionContainer
     */
    public function define(string $name, array $args): ReflectionContainer;

    /**
     * Assign a global default value for all parameters named $paramName
     *
     * Global parameter definitions are only used for parameters with no typehint, pre-defined or
     * call-time definition.
     *
     * @param string $paramName The parameter name for which this value applies
     * @param mixed  $value The value to inject for this parameter name
     * @return ReflectionContainer
     */
    public function defineParam(string $paramName, $value): ReflectionContainer;

    /**
     * Define an alias for all occurrences of a given typehint
     *
     * Use this method to specify implementation classes for interface and abstract class typehints.
     *
     * @param string $original The typehint to replace
     * @param string $alias    The implementation name
     * @throws ConfigException if any argument is empty or not a string
     * @return ReflectionContainer
     */
    public function alias(string $original, string $alias): ReflectionContainer;

    /**
     * Share the specified class/instance across the Injector context
     *
     * @param mixed $nameOrInstance The class or object to share
     * @throws ConfigException if $nameOrInstance is not a string or an object
     * @return ReflectionContainer
     */
    public function share(string|object $nameOrInstance): ReflectionContainer;

    /**
     * Register a prepare callable to modify/prepare objects of type $name after instantiation
     *
     * Any callable or provisionable invokable may be specified. Preparers are passed two
     * arguments: the instantiated object to be mutated and the current Injector instance.
     *
     * @param string                       $name Class name.
     * @param callable|string|array|object $callableOrMethodStr Any callable or provisionable invokable method
     * @throws InjectionException if $callableOrMethodStr is not a callable.
     *                            See https://docs.stalframework.com/injector/#injecting-for-execution
     * @return ReflectionContainer
     */
    public function prepare(string $name, callable|string|array|object $callableOrMethodStr): ReflectionContainer;

    /**
     * Delegate the creation of $name instances to the specified callable
     *
     * @param string                       $name Class name.
     * @param callable|string|array|object $callableOrMethodStr Any callable or provisionable invokable method.
     * @throws ConfigException if $callableOrMethodStr is not a callable.
     * @return ReflectionContainer
     */
    public function delegate(string $name, callable|string|array|object $callableOrMethodStr): ReflectionContainer;

    /**
     * Proxy the specified class across the Injector context.
     *
     * @param string $name The class to proxy
     * @param callable|string|array|object $callableOrMethodStr
     * @return ReflectionContainer
     * @throws ConfigException
     */
    public function proxy(string $name, callable|string|array|object $callableOrMethodStr): ReflectionContainer;

    /**
     * Instantiate/provision a class instance.
     *
     * @param string $name Name of an interface/class/alias to instantiate.
     * @param array  $args Optional arguments to pass to the object.
     * @return mixed
     * @throws InjectionException if a cyclic gets detected when provisioning
     */
    public function make(string $name, array $args = []);

    /**
     * Invoke the specified callable or class::method string, provisioning dependencies along the way.
     *
     * @param callable|string|array|object $callableOrMethodStr A valid PHP callable
     *                                                          or a provisionable ClassName::methodName string.
     * @param array                        $args                Optional array specifying params with which to
     *                                                          invoke the provisioned callable
     * @throws InjectionException
     * @return mixed Returns the invocation result returned from calling the generated executable
     */
    public function execute(callable|string|array|object $callableOrMethodStr, array $args = []);
}
