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
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Qubus\Exception\Exception;
use Qubus\Injector\Cache\CachingReflector;
use Qubus\Injector\Config\Config;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionParameter;
use Throwable;

use function array_flip;
use function array_intersect_key;
use function array_key_exists;
use function array_keys;
use function array_map;
use function array_merge;
use function array_replace;
use function array_walk;
use function call_user_func_array;
use function class_implements;
use function count;
use function explode;
use function function_exists;
use function get_class;
use function gettype;
use function implode;
use function is_array;
use function is_callable;
use function is_object;
use function is_string;
use function ltrim;
use function method_exists;
use function sprintf;
use function strpos;
use function strtolower;
use function substr;

class Injector
{
    public const A_RAW = ':';
    public const A_DELEGATE = '+';
    public const A_DEFINE = '@';

    public const I_BINDINGS = 1;
    public const I_DELEGATES = 2;
    public const I_PREPARES = 4;
    public const I_ALIASES = 8;
    public const I_SHARES = 16;
    public const I_ALL = 31;

    public const STANDARD_ALIASES     = 'standardAliases';
    public const SHARED_ALIASES       = 'sharedAliases';
    public const ARGUMENT_DEFINITIONS = 'argumentDefinitions';
    public const ARGUMENT_PROVIDERS   = 'argumentProviders';
    public const DELEGATIONS          = 'delegations';
    public const PREPARATIONS         = 'preparations';

    /** @var Reflector|null $reflector */
    protected ?Reflector $reflector = null;

    /** @var array $classDefinitions */
    protected array $classDefinitions = [];

    /** @var array $paramDefinitions */
    protected array $paramDefinitions = [];

    /** @var array $aliases */
    protected array $aliases = [];

    /** @var array $shares */
    protected array $shares = [];

    /** @var array $prepares */
    protected array $prepares = [];

    /** @var array $delegates */
    protected array $delegates = [];

    /** @var array $proxies */
    protected array $proxies = [];

    /** @var array $preparesProxy */
    protected array $preparesProxy = [];

    /** @var array $inProgressMakes */
    protected array $inProgressMakes = [];

    /** @var array $argumentDefinitions */
    protected array $argumentDefinitions = [];

    /** @var Config|null $config */
    protected ?Config $config = null;

    /**
     * Instantiate a Injector object.
     *
     * @param Config         $config Configuration array passed to the Injector.
     * @param Reflector|null $reflector Optional. Reflector class to use for traversal. Falls back to CachingReflector.
     * @throws FailedToProcessConfigException If the config file could not be processed.
     * @throws InvalidMappingsException       If the definitions could not be registered.
     */
    public function __construct(Config $config, ?Reflector $reflector = null)
    {
        $this->registerMappings($config);
        $this->reflector = $reflector ?: new CachingReflector();
    }

    /**
     * Don't share the instantiation chain across clones.
     */
    public function __clone()
    {
        $this->inProgressMakes = [];
    }

    /**
     * Register mapping definitions.
     *
     * Takes a Config and reads the following keys to add definitions:
     * - 'sharedAliases'
     * - 'standardAliases'
     * - 'argumentDefinitions'
     * - 'argumentProviders'
     * - 'delegations'
     * - 'preparations'
     *
     * @param Config $config Config array to parse.
     * @throws InvalidMappingsException If a needed key could not be read from the config file.
     * @throws InvalidMappingsException If the dependency injector could not be set up.
     */
    public function registerMappings(Config $config): void
    {
        $configKeys = [
            static::STANDARD_ALIASES     => 'mapAliases',
            static::SHARED_ALIASES       => 'shareAliases',
            static::ARGUMENT_DEFINITIONS => 'defineArguments',
            static::ARGUMENT_PROVIDERS   => 'defineArgumentProviders',
            static::DELEGATIONS          => 'defineDelegations',
            static::PREPARATIONS         => 'definePreparations',
        ];
        try {
            foreach ($configKeys as $key => $method) {
                $$key = $config->get($key, []);
            }

            $standardAliases = array_merge(
                $sharedAliases,
                $standardAliases
            );
        } catch (Exception $exception) {
            throw new InvalidMappingsException(
                sprintf(
                    'Failed to read needed keys from config. Reason: "%1$s".',
                    $exception->getMessage()
                )
            );
        }

        try {
            foreach ($configKeys as $key => $method) {
                array_walk($$key, [$this, $method]);
            }
        } catch (Exception $exception) {
            throw new InvalidMappingsException(
                sprintf(
                    'Failed to set up dependency injector. Reason: "%1$s".',
                    $exception->getMessage()
                )
            );
        }
    }

    /**
     * Map Interfaces to concrete classes for our Injector.
     *
     * @param string|object $class Concrete implementation to instantiate.
     * @param string $interface    Alias to register the implementation for.
     * @throws ConfigException If the alias could not be created.
     */
    protected function mapAliases($class, $interface): void
    {
        if ($class === $interface) {
            return;
        }
        $this->alias($interface, $class);
    }

    /**
     * Tell our Injector which interfaces to share across all requests.
     *
     * @param string|object $class Concrete implementation to instantiate.
     * @param string $interface    Alias to register the implementation for.
     * @throws ConfigException If the interface could not be shared.
     */
    protected function shareAliases($class, $interface)
    {
        $this->share($interface);
    }

    /**
     * Tell our Injector how arguments are defined.
     *
     * @param array  $argumentSetup Argument providers setup from configuration file.
     * @param string $alias         The alias for which to define the argument.
     * @throws InvalidMappingsException If a required config key could not be found.
     */
    protected function defineArguments(array $argumentSetup, $alias)
    {
        foreach ($argumentSetup as $key => $value) {
            $this->addArgumentDefinition($value, $alias, [$key, null]);
        }
    }

    /**
     * Tell our Injector what instantiations are delegated to factories.
     *
     * @param callable $factory Factory that will take care of the instantiation.
     * @param string   $alias   The alias for which to define the delegation.
     * @throws ConfigException If the delegation could not be configured.
     */
    protected function defineDelegations(callable $factory, $alias)
    {
        $this->delegate($alias, $factory);
    }

    /**
     * Tell our Injector what preparations need to be done.
     *
     * @param callable $preparation Preparation to execute on instantiation.
     * @param string   $alias       The alias for which to define the preparation.
     * @throws InvalidMappingsException If a required config key could not be found.
     * @throws InjectionException If the prepare statement was not valid.
     */
    protected function definePreparations(callable $preparation, $alias)
    {
        $this->prepare($alias, $preparation);
    }

    /**
     * Tell our Injector how to produce required arguments.
     *
     * @param array $argumentSetup Argument providers setup from configuration file.
     * @param string $argument     The argument to provide.
     * @throws InvalidMappingsException If a required config key could not be found.
     */
    protected function defineArgumentProviders($argumentSetup, $argument)
    {
        if (! array_key_exists('mappings', $argumentSetup)) {
            throw new InvalidMappingsException(
                sprintf(
                    'Failed to define argument providers for argument "%1$s". '
                      . 'Reason: The key "mappings" was not found.',
                    $argument
                )
            );
        }

        array_walk(
            $argumentSetup['mappings'],
            [$this, 'addArgumentDefinition'],
            [$argument, $argumentSetup['interface'] ?: null]
        );
    }

    /**
     * Add a single argument definition.
     *
     * @param callable $callable Callable to execute when the argument is needed.
     * @param string   $alias    Alias to add the argument definition to.
     * @param string   $args     Additional arguments used for definition. Array containing $argument & $interface.
     * @throws InvalidMappingsException If $callable is not a callable.
     */
    protected function addArgumentDefinition($callable, $alias, $args)
    {
        [$argument, $interface] = $args;

        $value = is_callable($callable)
        ? $this->getArgumentProxy($alias, $interface, $callable)
        : $callable;

        $argumentDefinition = array_key_exists($alias, $this->argumentDefinitions)
        ? $this->argumentDefinitions[$alias]
        : [];

        if ($value instanceof Injection) {
            $argumentDefinition[$argument] = $value->getAlias();
        } else {
            $argumentDefinition[":{$argument}"] = $value;
        }

        $this->argumentDefinitions[$alias] = $argumentDefinition;

        $this->define($alias, $this->argumentDefinitions[$alias]);
    }

    /**
     * Get an argument proxy for a given alias to provide to the injector.
     *
     * @param string   $alias     Alias that needs the argument.
     * @param string   $interface Interface that the proxy implements.
     * @param callable $callable  Callable used to initialize the proxy.
     * @return object Argument proxy to provide to the inspector.
     */
    protected function getArgumentProxy($alias, $interface, $callable)
    {
        if (null === $interface) {
            $interface = 'stdClass';
        }

        $factory     = new LazyLoadingValueHolderFactory();
        $initializer = function (
            &$wrappedObject,
            LazyLoadingInterface $proxy,
            $method,
            array $parameters,
            &$initializer
        ) use (
            $alias,
            $interface,
            $callable
        ) {
            $initializer   = null;
            $wrappedObject = $callable($alias, $interface);

            return true;
        };

        return $factory->createProxy($interface, $initializer);
    }

    /**
     * Define instantiation directives for the specified class
     *
     * @param string $name The class (or alias) whose constructor arguments we wish to define
     * @param array  $args An array mapping parameter names to values/instructions
     * @return self
     */
    public function define($name, array $args)
    {
        [, $normalizedName] = $this->resolveAlias($name);
        $this->classDefinitions[$normalizedName] = $args;

        return $this;
    }

    /**
     * Assign a global default value for all parameters named $paramName
     *
     * Global parameter definitions are only used for parameters with no typehint, pre-defined or
     * call-time definition.
     *
     * @param string $paramName The parameter name for which this value applies
     * @param mixed  $value The value to inject for this parameter name
     * @return self
     */
    public function defineParam($paramName, $value)
    {
        $this->paramDefinitions[$paramName] = $value;

        return $this;
    }

    /**
     * Define an alias for all occurrences of a given typehint
     *
     * Use this method to specify implementation classes for interface and abstract class typehints.
     *
     * @param string $original The typehint to replace
     * @param string $alias    The implementation name
     * @throws ConfigException if any argument is empty or not a string
     * @return self
     */
    public function alias($original, $alias)
    {
        if (empty($original) || ! is_string($original)) {
            throw new ConfigException(
                InjectorException::M_NON_EMPTY_STRING_ALIAS,
                InjectorException::E_NON_EMPTY_STRING_ALIAS
            );
        }
        if (empty($alias) || ! is_string($alias)) {
            throw new ConfigException(
                InjectorException::M_NON_EMPTY_STRING_ALIAS,
                InjectorException::E_NON_EMPTY_STRING_ALIAS
            );
        }

        $originalNormalized = $this->normalizeName($original);

        if (isset($this->shares[$originalNormalized])) {
            throw new ConfigException(
                sprintf(
                    InjectorException::M_SHARED_CANNOT_ALIAS,
                    $this->normalizeName(get_class($this->shares[$originalNormalized])),
                    $alias
                ),
                InjectorException::E_SHARED_CANNOT_ALIAS
            );
        }

        if (array_key_exists($originalNormalized, $this->shares)) {
            $aliasNormalized = $this->normalizeName($alias);
            $this->shares[$aliasNormalized] = null;
            unset($this->shares[$originalNormalized]);
        }

        $this->aliases[$originalNormalized] = $alias;

        return $this;
    }

    private function normalizeName($className)
    {
        return ltrim(strtolower($className), '\\');
    }

    /**
     * Share the specified class/instance across the Injector context
     *
     * @param mixed $nameOrInstance The class or object to share
     * @throws ConfigException if $nameOrInstance is not a string or an object
     * @return self
     */
    public function share(string|object $nameOrInstance)
    {
        if (is_string($nameOrInstance)) {
            $this->shareClass($nameOrInstance);
        } elseif (is_object($nameOrInstance)) {
            $this->shareInstance($nameOrInstance);
        } else {
            throw new ConfigException(
                sprintf(
                    InjectorException::M_SHARE_ARGUMENT,
                    self::class,
                    gettype($nameOrInstance)
                ),
                InjectorException::E_SHARE_ARGUMENT
            );
        }

        return $this;
    }

    private function shareClass($nameOrInstance)
    {
        [, $normalizedName] = $this->resolveAlias($nameOrInstance);
        $this->shares[$normalizedName] = $this->shares[$normalizedName] ?? null;
    }

    private function resolveAlias($name)
    {
        $normalizedName = $this->normalizeName($name);
        if (isset($this->aliases[$normalizedName])) {
            $name = $this->aliases[$normalizedName];
            $normalizedName = $this->normalizeName($name);
        }

        return [$name, $normalizedName];
    }

    private function shareInstance($obj)
    {
        $normalizedName = $this->normalizeName(get_class($obj));
        if (isset($this->aliases[$normalizedName])) {
            // You cannot share an instance of a class name that is already aliased
            throw new ConfigException(
                sprintf(
                    InjectorException::M_ALIASED_CANNOT_SHARE,
                    $normalizedName,
                    $this->aliases[$normalizedName]
                ),
                InjectorException::E_ALIASED_CANNOT_SHARE
            );
        }
        $this->shares[$normalizedName] = $obj;
    }

    /**
     * Register a prepare callable to modify/prepare objects of type $name after instantiation
     *
     * Any callable or provisionable invokable may be specified. Preparers are passed two
     * arguments: the instantiated object to be mutated and the current Injector instance.
     *
     * @param string $name
     * @param mixed $callableOrMethodStr Any callable or provisionable invokable method
     * @throws InjectionException if $callableOrMethodStr is not a callable.
     *                            See https://docs.stalframework.com/injector/#injecting-for-execution
     * @return self
     */
    public function prepare($name, $callableOrMethodStr)
    {
        if ($this->isExecutable($callableOrMethodStr) === false) {
            throw InjectionException::fromInvalidCallable(
                $this->inProgressMakes,
                InjectorException::E_INVOKABLE,
                $callableOrMethodStr
            );
        }

        [, $normalizedName] = $this->resolveAlias($name);
        $this->prepares[$normalizedName] = $callableOrMethodStr;

        return $this;
    }

    private function isExecutable($exe)
    {
        if (is_callable($exe)) {
            return true;
        }
        if (is_string($exe) && method_exists($exe, '__invoke')) {
            return true;
        }
        if (is_array($exe) && isset($exe[0], $exe[1]) && method_exists($exe[0], $exe[1])) {
            return true;
        }

        return false;
    }

    /**
     * Delegate the creation of $name instances to the specified callable
     *
     * @param string $name
     * @param mixed $callableOrMethodStr Any callable or provisionable invokable method.
     * @throws ConfigException if $callableOrMethodStr is not a callable.
     * @return self
     */
    public function delegate($name, $callableOrMethodStr)
    {
        if ($this->isExecutable($callableOrMethodStr) === false) {
            $this->generateInvalidCallableError($callableOrMethodStr);
        }
        $normalizedName = $this->normalizeName($name);
        $this->delegates[$normalizedName] = $callableOrMethodStr;

        return $this;
    }

    /**
     * Retrieve stored data for the specified definition type.
     *
     * Exposes introspection of existing binds/delegates/shares/etc for decoration and composition.
     *
     * @param string $nameFilter An optional class name filter
     * @param int    $typeFilter A bitmask of Injector::* type constant flags.
     * @return array
     */
    public function inspect(?string $nameFilter = null, ?int $typeFilter = null)
    {
        $result = [];
        $name = $nameFilter ? $this->normalizeName($nameFilter) : null;

        if (empty($typeFilter)) {
            $typeFilter = self::I_ALL;
        }

        $types = [
            self::I_BINDINGS  => "classDefinitions",
            self::I_DELEGATES => "delegates",
            self::I_PREPARES  => "prepares",
            self::I_ALIASES   => "aliases",
            self::I_SHARES    => "shares",
        ];

        foreach ($types as $type => $source) {
            if ($typeFilter & $type) {
                $result[$type] = $this->filter($this->{$source}, $name);
            }
        }

        return $result;
    }

    private function filter($source, $name)
    {
        if (empty($name)) {
            return $source;
        } elseif (array_key_exists($name, $source)) {
            return [$name => $source[$name]];
        } else {
            return [];
        }
    }

    /**
     * Proxy the specified class across the Injector context.
     *
     * @param string $name The class to proxy
     * @param $callableOrMethodStr
     * @return Injector
     * @throws ConfigException
     */
    public function proxy(string $name, $callableOrMethodStr)
    {
        if (! $this->isExecutable($callableOrMethodStr)) {
            $this->generateInvalidCallableError($callableOrMethodStr);
        }

        [$className, $normalizedName] = $this->resolveAlias($name);
        $this->proxies[$normalizedName] = $callableOrMethodStr;

        return $this;
    }

    /**
     * Instantiate/provision a class instance.
     *
     * @param string $name Name of an interface/class/alias to instantiate.
     * @param array  $args Optional arguments to pass to the object.
     * @return mixed
     * @throws InjectionException if a cyclic gets detected when provisioning
     */
    public function make($name, array $args = [])
    {
        [$className, $normalizedClass] = $this->resolveAlias($name);

        if (isset($this->inProgressMakes[$normalizedClass])) {
            throw new InjectionException(
                $this->inProgressMakes,
                sprintf(
                    InjectorException::M_CYCLIC_DEPENDENCY,
                    $className
                ),
                InjectorException::E_CYCLIC_DEPENDENCY
            );
        }

        $this->inProgressMakes[$normalizedClass] = count($this->inProgressMakes);

        // isset() is used specifically here because classes may be marked as "shared" before an
        // instance is stored. In these cases the class is "shared," but it has a null value and
        // instantiation is needed.
        if (isset($this->shares[$normalizedClass])) {
            unset($this->inProgressMakes[$normalizedClass]);

            return $this->shares[$normalizedClass];
        }

        try {
            if (isset($this->delegates[$normalizedClass])) {
                $executable         = $this->buildExecutable($this->delegates[$normalizedClass]);
                $reflectionFunction = $executable->getCallableReflection();
                $args               = $this->provisionFuncArgs($reflectionFunction, $args);
                $obj                = call_user_func_array([$executable, '__invoke'], $args);
            } else {
                $obj = $this->provisionInstance($className, $normalizedClass, $args);
            }

            $obj = $this->prepareInstance($obj, $normalizedClass);

            if (array_key_exists($normalizedClass, $this->shares)) {
                $this->shares[$normalizedClass] = $obj;
            }

            unset($this->inProgressMakes[$normalizedClass]);
        } catch (Exception $exception) {
            unset($this->inProgressMakes[$normalizedClass]);
            throw $exception;
        } catch (Throwable $exception) {
            unset($this->inProgressMakes[$normalizedClass]);
            throw $exception;
        }

        return $obj;
    }

    private function resolveProxy(string $className, string $normalizedClass, array $args)
    {
        $callback = fn () => $this->buildWrappedObject($className, $normalizedClass, $args);

        $proxy = $this->proxies[$normalizedClass];

        return $proxy($className, $callback);
    }

    /**
     * @param string $className
     * @param string $normalizedClass
     * @param array $args
     * @return mixed|object
     * @throws InjectionException
     */
    private function buildWrappedObject($className, $normalizedClass, array $args)
    {
        $wrappedObject = $this->provisionInstance($className, $normalizedClass, $args);

        if (isset($this->preparesProxy[ $normalizedClass ])) {
            $this->prepares[ $normalizedClass ] = $this->preparesProxy[ $normalizedClass ];
        }

        return $this->prepareInstance($wrappedObject, $normalizedClass);
    }

    private function provisionInstance($className, $normalizedClass, array $definition)
    {
        try {
            $ctor = $this->reflector->getConstructor($className);

            if (! $ctor) {
                $obj = $this->instantiateWithoutConstructorParams($className);
            } elseif (! $ctor->isPublic()) {
                throw new InjectionException(
                    $this->inProgressMakes,
                    sprintf(InjectorException::M_NON_PUBLIC_CONSTRUCTOR, $className),
                    InjectorException::E_NON_PUBLIC_CONSTRUCTOR
                );
            } elseif ($ctorParams = $this->reflector->getConstructorParams($className)) {
                $reflClass = $this->reflector->getClass($className);
                $definition = isset($this->classDefinitions[$normalizedClass])
                ? array_replace($this->classDefinitions[$normalizedClass], $definition)
                : $definition;
                $args = $this->provisionFuncArgs($ctor, $definition, $ctorParams);
                $obj = $reflClass->newInstanceArgs($args);
            } else {
                $obj = $this->instantiateWithoutConstructorParams($className);
            }

            return $obj;
        } catch (ReflectionException $e) {
            throw new InjectionException(
                $this->inProgressMakes,
                sprintf(InjectorException::M_MAKE_FAILURE, $className, $e->getMessage()),
                InjectorException::E_MAKE_FAILURE,
                $e
            );
        }
    }

    private function instantiateWithoutConstructorParams($className)
    {
        $reflClass = $this->reflector->getClass($className);

        if (! $reflClass->isInstantiable()) {
            $type = $reflClass->isInterface() ? 'interface' : 'abstract class';
            throw new InjectionException(
                $this->inProgressMakes,
                sprintf(InjectorException::M_NEEDS_DEFINITION, $type, $className),
                InjectorException::E_NEEDS_DEFINITION
            );
        }

        return new $className();
    }

    private function provisionFuncArgs(
        ReflectionFunctionAbstract $reflFunc,
        array $definition,
        ?array $reflParams = null
    ) {
        $args = [];

        // @TODO store this in ReflectionStorage
        if (! isset($reflParams)) {
            $reflParams = $reflFunc->getParameters();
        }

        foreach ($reflParams as $i => $reflParam) {
            $name = $reflParam->name;

            if (isset($definition[$i]) || array_key_exists($i, $definition)) {
                // indexed arguments take precedence over named parameters
                $arg = $definition[$i];
            } elseif (isset($definition[$name]) || array_key_exists($name, $definition)) {
                // interpret the param as a class name to be instantiated
                $arg = $this->make($definition[$name]);
            } elseif (
                ($prefix = static::A_RAW . $name) && (isset($definition[$prefix]) ||
                array_key_exists($prefix, $definition))
            ) {
                // interpret the param as a raw value to be injected
                $arg = $definition[$prefix];
            } elseif (($prefix = static::A_DELEGATE . $name) && isset($definition[$prefix])) {
                // interpret the param as an invokable delegate
                $arg = $this->buildArgFromDelegate($name, $definition[$prefix]);
            } elseif (($prefix = static::A_DEFINE . $name) && isset($definition[$prefix])) {
                // interpret the param as a class definition
                $arg = $this->buildArgFromParamDefineArr($definition[$prefix]);
            } elseif (! $arg = $this->buildArgFromTypeHint($reflFunc, $reflParam)) {
                $arg = $this->buildArgFromReflParam($reflParam);
            }

            $args[] = $arg;
        }

        return $args;
    }

    private function buildArgFromParamDefineArr($definition)
    {
        if (! is_array($definition)) {
            throw new InjectionException(
                $this->inProgressMakes
                // @TODO Add message
            );
        }

        if (! isset($definition[0], $definition[1])) {
            throw new InjectionException(
                $this->inProgressMakes
                // @TODO Add message
            );
        }

        [$class, $definition] = $definition;

        return $this->make($class, $definition);
    }

    private function buildArgFromDelegate($paramName, $callableOrMethodStr)
    {
        if ($this->isExecutable($callableOrMethodStr) === false) {
            throw InjectionException::fromInvalidCallable(
                $this->inProgressMakes,
                $callableOrMethodStr
            );
        }

        $executable = $this->buildExecutable($callableOrMethodStr);

        return $executable($paramName, $this);
    }

    private function buildArgFromTypeHint(ReflectionFunctionAbstract $reflFunc, ReflectionParameter $reflParam)
    {
        $typeHint = $this->reflector->getParamTypeHint($reflFunc, $reflParam);

        if (! $typeHint) {
            $obj = null;
        } elseif ($reflParam->isDefaultValueAvailable()) {
            $normalizedName = $this->normalizeName($typeHint);
            // Injector has been told explicitly how to make this type
            if (
                isset($this->aliases[$normalizedName]) ||
                isset($this->delegates[$normalizedName]) ||
                isset($this->shares[$normalizedName])
            ) {
                $obj = $this->make($typeHint);
            } else {
                $obj = $reflParam->getDefaultValue();
            }
        } else {
            $obj = $this->make($typeHint);
        }

        return $obj;
    }

    private function buildArgFromReflParam(ReflectionParameter $reflParam)
    {
        if (array_key_exists($reflParam->name, $this->paramDefinitions)) {
            $arg = $this->paramDefinitions[$reflParam->name];
        } elseif ($reflParam->isDefaultValueAvailable()) {
            $arg = $reflParam->getDefaultValue();
        } elseif ($reflParam->isOptional()) {
            // This branch is required to work around PHP bugs where a parameter is optional
            // but has no default value available through reflection. Specifically, PDO exhibits
            // this behavior.
            $arg = null;
        } else {
            $reflFunc = $reflParam->getDeclaringFunction();
            $classWord = $reflFunc instanceof ReflectionMethod
            ? $reflFunc->getDeclaringClass()->name . '::'
            : '';
            $funcWord = $classWord . $reflFunc->name;

            throw new InjectionException(
                $this->inProgressMakes,
                sprintf(
                    InjectorException::M_UNDEFINED_PARAM,
                    $reflParam->name,
                    $reflParam->getPosition(),
                    $funcWord,
                    implode(' => ', array_keys($this->inProgressMakes))
                ),
                InjectorException::E_UNDEFINED_PARAM
            );
        }

        return $arg;
    }

    private function prepareInstance($obj, $normalizedClass)
    {
        if (isset($this->prepares[$normalizedClass])) {
            $prepare = $this->prepares[$normalizedClass];
            $executable = $this->buildExecutable($prepare);
            $result = $executable($obj, $this);
            if ($result instanceof $normalizedClass) {
                $obj = $result;
            }
        }

        $interfaces = class_implements($obj);

        if ($interfaces === false) {
            throw new InjectionException(
                $this->inProgressMakes,
                sprintf(
                    InjectorException::M_MAKING_FAILED,
                    $normalizedClass,
                    gettype($obj)
                ),
                InjectorException::E_MAKING_FAILED
            );
        }

        if (empty($interfaces)) {
            return $obj;
        }

        $interfaces = array_flip(array_map([$this, 'normalizeName'], $interfaces));
        $prepares = array_intersect_key($this->prepares, $interfaces);
        foreach ($prepares as $interfaceName => $prepare) {
            $executable = $this->buildExecutable($prepare);
            $result = $executable($obj, $this);
            if ($result instanceof $normalizedClass) {
                $obj = $result;
            }
        }

        return $obj;
    }

    /**
     * Invoke the specified callable or class::method string, provisioning dependencies along the way
     *
     * @param mixed $callableOrMethodStr A valid PHP callable or a provisionable ClassName::methodName string
     * @param array $args                Optional array specifying params with which to invoke the provisioned callable
     * @throws \Injector\InjectionException
     * @return mixed Returns the invocation result returned from calling the generated executable
     */
    public function execute($callableOrMethodStr, array $args = [])
    {
        [$reflFunc, $invocationObj] = $this->buildExecutableStruct($callableOrMethodStr);
        $executable = new Executable($reflFunc, $invocationObj);
        $args = $this->provisionFuncArgs($reflFunc, $args);

        return call_user_func_array([$executable, '__invoke'], $args);
    }

    /**
     * Provision an Executable instance from any valid callable or class::method string
     *
     * @param mixed $callableOrMethodStr A valid PHP callable or a provisionable ClassName::methodName string
     * @return \Injector\Executable
     * @throws InjectionException If the Executable structure could not be built.
     */
    public function buildExecutable($callableOrMethodStr)
    {
        try {
            [$reflFunc, $invocationObj] = $this->buildExecutableStruct($callableOrMethodStr);
        } catch (ReflectionException $e) {
            throw InjectionException::fromInvalidCallable(
                $this->inProgressMakes,
                $callableOrMethodStr,
                $e
            );
        }

        return new Executable($reflFunc, $invocationObj);
    }

    private function buildExecutableStruct($callableOrMethodStr)
    {
        if (is_string($callableOrMethodStr)) {
            $executableStruct = $this->buildExecutableStructFromString($callableOrMethodStr);
        } elseif ($callableOrMethodStr instanceof Closure) {
            $callableRefl = new ReflectionFunction($callableOrMethodStr);
            $executableStruct = [$callableRefl, null];
        } elseif (is_object($callableOrMethodStr) && is_callable($callableOrMethodStr)) {
            $invocationObj = $callableOrMethodStr;
            $callableRefl = $this->reflector->getMethod($invocationObj, '__invoke');
            $executableStruct = [$callableRefl, $invocationObj];
        } elseif (
            is_array($callableOrMethodStr)
            && isset($callableOrMethodStr[0], $callableOrMethodStr[1])
            && count($callableOrMethodStr) === 2
        ) {
            $executableStruct = $this->buildExecutableStructFromArray($callableOrMethodStr);
        } else {
            throw InjectionException::fromInvalidCallable(
                $this->inProgressMakes,
                $callableOrMethodStr
            );
        }

        return $executableStruct;
    }

    private function buildExecutableStructFromString($stringExecutable)
    {
        if (function_exists($stringExecutable)) {
            $callableRefl = $this->reflector->getFunction($stringExecutable);
            $executableStruct = [$callableRefl, null];
        } elseif (method_exists($stringExecutable, '__invoke')) {
            $invocationObj = $this->make($stringExecutable);
            $callableRefl = $this->reflector->getMethod($invocationObj, '__invoke');
            $executableStruct = [$callableRefl, $invocationObj];
        } elseif (strpos($stringExecutable, '::') !== false) {
            [$class, $method] = explode('::', $stringExecutable, 2);
            $executableStruct = $this->buildStringClassMethodCallable($class, $method);
        } else {
            throw InjectionException::fromInvalidCallable(
                $this->inProgressMakes,
                $stringExecutable
            );
        }

        return $executableStruct;
    }

    private function buildStringClassMethodCallable($class, $method)
    {
        $relativeStaticMethodStartPos = strpos($method, 'parent::');

        if ($relativeStaticMethodStartPos === 0) {
            $childReflection = $this->reflector->getClass($class);
            $class = $childReflection->getParentClass()->name;
            $method = substr($method, $relativeStaticMethodStartPos + 8);
        }

        [$className, $normalizedClass] = $this->resolveAlias($class);
        $reflectionMethod = $this->reflector->getMethod($className, $method);

        if ($reflectionMethod->isStatic()) {
            return [$reflectionMethod, null];
        }

        $instance = $this->make($className);
        // If the class was delegated, the instance may not be of the type
        // $class but some other type. We need to get the reflection on the
        // actual class to be able to call the method correctly.
        $reflectionMethod = $this->reflector->getMethod($instance, $method);

        return [$reflectionMethod, $instance];
    }

    private function buildExecutableStructFromArray($arrayExecutable)
    {
        [$classOrObj, $method] = $arrayExecutable;

        if (is_object($classOrObj) && method_exists($classOrObj, $method)) {
            $callableRefl = $this->reflector->getMethod($classOrObj, $method);
            $executableStruct = [$callableRefl, $classOrObj];
        } elseif (is_string($classOrObj)) {
            $executableStruct = $this->buildStringClassMethodCallable($classOrObj, $method);
        } else {
            throw InjectionException::fromInvalidCallable(
                $this->inProgressMakes,
                $arrayExecutable
            );
        }

        return $executableStruct;
    }

    /**
     * Get the chain of instantiations.
     *
     * @return InjectionChain Chain of instantiations.
     */
    public function getInjectionChain()
    {
        return new InjectionChain($this->inProgressMakes);
    }

    /**
     * @param $callableOrMethodStr
     * @throws ConfigException
     */
    private function generateInvalidCallableError($callableOrMethodStr)
    {
        $errorDetail = '';
        if (is_string($callableOrMethodStr)) {
            $errorDetail = " but received '$callableOrMethodStr'";
        } elseif (
            is_array($callableOrMethodStr) &&
            count($callableOrMethodStr) === 2 &&
            array_key_exists(0, $callableOrMethodStr) &&
            array_key_exists(1, $callableOrMethodStr)
        ) {
            if (is_string($callableOrMethodStr[0]) && is_string($callableOrMethodStr[1])) {
                $errorDetail = " but received ['" . $callableOrMethodStr[0] . "', '" . $callableOrMethodStr[1] . "']";
            }
        }
        throw new ConfigException(
            sprintf(InjectionException::M_DELEGATE_ARGUMENT, self::class, $errorDetail),
            InjectionException::E_DELEGATE_ARGUMENT
        );
    }
}
