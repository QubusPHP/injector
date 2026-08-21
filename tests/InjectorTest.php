<?php

declare(strict_types=1);

namespace Qubus\Injector\Test;

use Closure;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use Qubus\Injector\Config\Config;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\ConfigException;
use Qubus\Injector\InjectionChain;
use Qubus\Injector\InjectionException;
use Qubus\Injector\Injector;
use Qubus\Injector\InjectorException;
use Qubus\Injector\Test\Fixture\BaseExecutableClass;
use Qubus\Injector\Test\Fixture\BaseExecute;
use Qubus\Injector\Test\Fixture\CallableMock;
use Qubus\Injector\Test\Fixture\ChildWithoutConstructor;
use Qubus\Injector\Test\Fixture\CloneInjector;
use Qubus\Injector\Test\Fixture\ConcreteExecute;
use Qubus\Injector\Test\Fixture\DelegateClosureInGlobalScope;
use Qubus\Injector\Test\Fixture\ExtendsExecutableClass;
use Qubus\Injector\Test\Fixture\InjectionChainTestDependency;
use Qubus\Injector\Test\Fixture\InjectionChainValue;
use Qubus\Injector\Test\Fixture\ParentWithConstructor;
use Qubus\Injector\Test\Fixture\ReturnsCallable;
use Qubus\Injector\Test\Fixture\CallableDelegateClassTest;
use Qubus\Injector\Test\Fixture\ClassInnerA;
use Qubus\Injector\Test\Fixture\ClassInnerB;
use Qubus\Injector\Test\Fixture\ClassOuter;
use Qubus\Injector\Test\Fixture\ClassWithAliasAsParameter;
use Qubus\Injector\Test\Fixture\ClassWithCtor;
use Qubus\Injector\Test\Fixture\ClassWithStaticMethodThatTakesArg;
use Qubus\Injector\Test\Fixture\ClassWithoutMagicInvoke;
use Qubus\Injector\Test\Fixture\ConcreteClass1;
use Qubus\Injector\Test\Fixture\ConcreteClass2;
use Qubus\Injector\Test\Fixture\ConcreteDependencyWithDefaultValue;
use Qubus\Injector\Test\Fixture\ConfigClass;
use Qubus\Injector\Test\Fixture\DelegatableInterface;
use Qubus\Injector\Test\Fixture\DepImplementation;
use Qubus\Injector\Test\Fixture\DepInterface;
use Qubus\Injector\Test\Fixture\DependencyWithDefinedParam;
use Qubus\Injector\Test\Fixture\DependsOnCyclic;
use Qubus\Injector\Test\Fixture\ExecuteClassDeps;
use Qubus\Injector\Test\Fixture\ExecuteClassDepsWithMethodDeps;
use Qubus\Injector\Test\Fixture\ExecuteClassInvokable;
use Qubus\Injector\Test\Fixture\ExecuteClassNoDeps;
use Qubus\Injector\Test\Fixture\ExecuteClassRelativeStaticMethod;
use Qubus\Injector\Test\Fixture\ExecuteClassStaticMethod;
use Qubus\Injector\Test\Fixture\HasNonPublicConstructor;
use Qubus\Injector\Test\Fixture\HasNonPublicConstructorWithArgs;
use Qubus\Injector\Test\Fixture\ImplementsInterface;
use Qubus\Injector\Test\Fixture\ImplementsInterfaceFactory;
use Qubus\Injector\Test\Fixture\InaccessibleExecutableClassMethod;
use Qubus\Injector\Test\Fixture\InaccessibleStaticExecutableClassMethod;
use Qubus\Injector\Test\Fixture\InjectorTestChildClass;
use Qubus\Injector\Test\Fixture\InjectorTestCtorParamWithNoTypehintOrDefault;
use Qubus\Injector\Test\Fixture\InjectorTestCtorParamWithNoTypehintOrDefaultDependent;
use Qubus\Injector\Test\Fixture\InjectorTestParentClass;
use Qubus\Injector\Test\Fixture\InjectorTestRawCtorParams;
use Qubus\Injector\Test\Fixture\MadeByDelegate;
use Qubus\Injector\Test\Fixture\NoTypehintNoDefaultConstructorClass;
use Qubus\Injector\Test\Fixture\NoTypehintNullDefaultConstructorClass;
use Qubus\Injector\Test\Fixture\NonConcreteDependencyWithDefaultValue;
use Qubus\Injector\Test\Fixture\NotSharedClass;
use Qubus\Injector\Test\Fixture\PreparesImplementationTest;
use Qubus\Injector\Test\Fixture\ProvTestNoDefinitionNullDefaultClass;
use Qubus\Injector\Test\Fixture\ProviderTestCtorParamWithNoTypehintOrDefault;
use Qubus\Injector\Test\Fixture\ProviderTestCtorParamWithNoTypehintOrDefaultDependent;
use Qubus\Injector\Test\Fixture\RecursiveClass1;
use Qubus\Injector\Test\Fixture\RecursiveClass2;
use Qubus\Injector\Test\Fixture\RecursiveClassA;
use Qubus\Injector\Test\Fixture\RecursiveClassB;
use Qubus\Injector\Test\Fixture\RecursiveClassC;
use Qubus\Injector\Test\Fixture\RequiresDelegatedInterface;
use Qubus\Injector\Test\Fixture\RequiresDependencyWithDefinedParam;
use Qubus\Injector\Test\Fixture\RequiresDependencyWithTypelessParameters;
use Qubus\Injector\Test\Fixture\RequiresInterface;
use Qubus\Injector\Test\Fixture\SharedAliasedInterface;
use Qubus\Injector\Test\Fixture\SharedClass;
use Qubus\Injector\Test\Fixture\SimpleNoTypehintClass;
use Qubus\Injector\Test\Fixture\SomeClassName;
use Qubus\Injector\Test\Fixture\SomeImplementation;
use Qubus\Injector\Test\Fixture\SomeInterface;
use Qubus\Injector\Test\Fixture\SpecdTestDependency;
use Qubus\Injector\Test\Fixture\StringDelegateWithNoInvokeMethod;
use Qubus\Injector\Test\Fixture\StringStdClassDelegateMock;
use Qubus\Injector\Test\Fixture\TestClassWithNoCtorTypehints;
use Qubus\Injector\Test\Fixture\TestDelegationDependency;
use Qubus\Injector\Test\Fixture\TestDelegationSimple;
use Qubus\Injector\Test\Fixture\TestDependency;
use Qubus\Injector\Test\Fixture\TestDependency2;
use Qubus\Injector\Test\Fixture\TestDependencyWithProtectedConstructor;
use Qubus\Injector\Test\Fixture\TestMissingDependency;
use Qubus\Injector\Test\Fixture\TestMultiDepsNeeded;
use Qubus\Injector\Test\Fixture\TestMultiDepsWithCtor;
use Qubus\Injector\Test\Fixture\TestNeedsDep;
use Qubus\Injector\Test\Fixture\TestNeedsDepWithProtCons;
use Qubus\Injector\Test\Fixture\TestNoConstructor;
use Qubus\Injector\Test\Fixture\TestNoExplicitDefine;
use Qubus\Injector\Test\Fixture\TypelessParameterDependency;
use stdClass;
use const PHP_VERSION_ID;

class InjectorTest extends TestCase
{
    public function testInstanceProxy()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->proxy(
            TestDependency::class,
            static function (string $className, callable $callback) {
                return new LazyLoadingValueHolderFactory()->createProxy(
                    $className,
                    static function (&$object, $proxy, $method, $parameters, &$initializer) use ($callback) {
                        $object = $callback();
                        $initializer = null;
                    }
                );
            }
        );

        $class = $injector->make(TestDependency::class);

        Assert::assertInstanceOf(TestDependency::class, $class);
        Assert::assertInstanceOf(LazyLoadingInterface::class, $class);
        Assert::assertEquals('testVal', $class->testProp);
    }
    public function testMakeInstancesThroughConfigAlias()
    {
        $injector = new Injector(InjectorFactory::create([
            Injector::STANDARD_ALIASES => [
                'bn.foo' => NotSharedClass::class,
            ],
            Injector::SHARED_ALIASES   => [
                'BNBar' => SharedClass::class,
            ],
        ]));

        $objFooA  = $injector->make('bn.foo');
        $objFooB  = $injector->make('bn.foo');
        $objBarA  = $injector->make('BNBar');
        $objBarB  = $injector->make('BNBar');
        Assert::assertInstanceOf(
            NotSharedClass::class,
            $objFooA
        );
        Assert::assertInstanceOf(
            NotSharedClass::class,
            $objFooB
        );
        Assert::assertInstanceOf(
            SharedClass::class,
            $objBarA
        );
        Assert::assertInstanceOf(
            SharedClass::class,
            $objBarB
        );
        Assert::assertNotSame($objFooA, $objFooB);
        Assert::assertSame($objBarA, $objBarB);
    }

    public function testArgumentDefinitionsThroughConfig()
    {
        $injector = new Injector(InjectorFactory::create([
            Injector::ARGUMENT_DEFINITIONS => [
                DependencyWithDefinedParam::class => [
                    'foo' => 42,
                ],
            ],
        ]));

        $obj = $injector->make(DependencyWithDefinedParam::class);
        Assert::assertEquals(42, $obj->foo);
    }

    public function testDelegationsThroughConfig()
    {
        $injector = new Injector(InjectorFactory::create([
            Injector::DELEGATIONS => [
                'stdClass' => function () {
                    return new SomeClassName();
                },
            ],
        ]));

        $obj = $injector->make(stdClass::class);
        Assert::assertInstanceOf(SomeClassName::class, $obj);
    }

    public function testPreparationsThroughConfig()
    {
        $injector = new Injector(InjectorFactory::create([
            Injector::PREPARATIONS => [
                'stdClass'           => function ($obj, $injector) {
                    $obj->testval = 42;
                },
                SomeInterface::class => function ($obj, $injector) {
                    $obj->testProp = 42;
                },
            ],
        ]));

        $obj1 = $injector->make(stdClass::class);
        Assert::assertSame(42, $obj1->testval);
        $obj2 = $injector->make(PreparesImplementationTest::class);
        Assert::assertSame(42, $obj2->testProp);
    }

    public function testMakeInstanceInjectsSimpleConcreteDependency()
    {
        $injector = new Injector(InjectorFactory::create([]));
        Assert::assertEquals(
            new TestNeedsDep(new TestDependency()),
            $injector->make(TestNeedsDep::class)
        );
    }

    public function testMakeInstanceReturnsNewInstanceIfClassHasNoConstructor()
    {
        $injector = new Injector(InjectorFactory::create([]));
        Assert::assertEquals(
            new TestNoConstructor(),
            $injector->make(TestNoConstructor::class)
        );
    }

    public function testMakeInstanceReturnsAliasInstanceOnNonConcreteTypehint()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            DepInterface::class,
            DepImplementation::class
        );
        Assert::assertEquals(
            new DepImplementation(),
            $injector->make(DepInterface::class)
        );
    }

    public function testMakeInstanceThrowsExceptionOnInterfaceWithoutAlias()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_NEEDS_DEFINITION);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->make(DepInterface::class);
    }

    public function testMakeInstanceThrowsExceptionOnNonConcreteCtorParamWithoutImplementation()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_NEEDS_DEFINITION);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->make(RequiresInterface::class);
    }

    public function testMakeInstanceBuildsNonConcreteCtorParamWithAlias()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            DepInterface::class,
            DepImplementation::class
        );
        $obj = $injector->make(RequiresInterface::class);
        Assert::assertInstanceOf(
            RequiresInterface::class,
            $obj
        );
    }

    public function testMakeInstancePassesNullCtorParameterIfNoTypehintOrDefaultCanBeDetermined()
    {
        $injector         = new Injector(InjectorFactory::create([]));
        $nullCtorParamObj = $injector->make(ProvTestNoDefinitionNullDefaultClass::class);
        Assert::assertEquals(new ProvTestNoDefinitionNullDefaultClass(), $nullCtorParamObj);
        Assert::assertEquals(null, $nullCtorParamObj->arg);
    }

    public function testMakeInstanceReturnsSharedInstanceIfAvailable()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->define(
            RequiresInterface::class,
            ['dep' => DepImplementation::class]
        );
        $injector->share(RequiresInterface::class);
        $injected = $injector->make(RequiresInterface::class);

        Assert::assertEquals('something', $injected->testDep->testProp);
        $injected->testDep->testProp = 'something else';

        $injected2 = $injector->make(RequiresInterface::class);
        Assert::assertEquals('something else', $injected2->testDep->testProp);
    }

    public function testMakeInstanceThrowsExceptionOnClassLoadFailure()
    {
        $this->expectException(InjectionException::class);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->make('ClassThatDoesntExist');
    }

    public function testMakeInstanceUsesCustomDefinitionIfSpecified()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->define(
            TestNeedsDep::class,
            ['testDep' => TestDependency::class]
        );
        $injected = $injector->make(
            TestNeedsDep::class,
            ['testDep' => TestDependency2::class]
        );
        Assert::assertEquals('testVal2', $injected->testDep->testProp);
    }

    public function testMakeInstanceCustomDefinitionOverridesExistingDefinitions()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->define(
            InjectorTestChildClass::class,
            [
                ':arg1' => 'First argument',
                ':arg2' => 'Second argument',
            ]
        );
        $injected = $injector->make(
            InjectorTestChildClass::class,
            [':arg1' => 'Override']
        );
        Assert::assertEquals('Override', $injected->arg1);
        Assert::assertEquals('Second argument', $injected->arg2);
    }

    public function testMakeInstanceStoresShareIfMarkedWithNullInstance()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(TestDependency::class);
        $obj = $injector->make(TestDependency::class);
        Assert::assertInstanceOf(TestDependency::class, $obj);
    }

    public function testMakeInstanceUsesReflectionForUnknownParamsInMultiBuildWithDeps()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $obj      = $injector->make(
            TestMultiDepsWithCtor::class,
            ['val1' => TestDependency::class]
        );
        Assert::assertInstanceOf(TestMultiDepsWithCtor::class, $obj);

        $obj = $injector->make(
            NoTypehintNoDefaultConstructorClass::class,
            ['val1' => TestDependency::class]
        );
        Assert::assertInstanceOf(NoTypehintNoDefaultConstructorClass::class, $obj);
        Assert::assertEquals(null, $obj->testParam);
    }

    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefault()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(InjectorFactory::create([]));
        $obj      = $injector->make(InjectorTestCtorParamWithNoTypehintOrDefault::class);
        Assert::assertNull($obj->val);
    }

    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefaultThroughAliasedTypehint()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            TestNoExplicitDefine::class,
            InjectorTestCtorParamWithNoTypehintOrDefault::class
        );
        $injector->make(InjectorTestCtorParamWithNoTypehintOrDefaultDependent::class);
    }

    public function testMakeInstanceThrowsExceptionOnUninstantiableTypehintWithoutDefinition()
    {
        $this->expectException(InjectionException::class);

        $injector = new Injector(InjectorFactory::create([]));
        $obj      = $injector->make(RequiresInterface::class);
    }

    public function testTypelessDefineForDependency()
    {
        $thumbnailSize = 128;
        $injector      = new Injector(InjectorFactory::create([]));
        $injector->defineParam('thumbnailSize', $thumbnailSize);
        $testClass = $injector->make(RequiresDependencyWithTypelessParameters::class);
        Assert::assertEquals(
            $thumbnailSize,
            $testClass->getThumbnailSize(),
            'Typeless define was not injected correctly.'
        );
    }

    public function testTypelessDefineForAliasedDependency()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->defineParam('val', 42);

        $injector->alias(
            TestNoExplicitDefine::class,
            ProviderTestCtorParamWithNoTypehintOrDefault::class
        );
        $obj = $injector->make(ProviderTestCtorParamWithNoTypehintOrDefaultDependent::class);
        Assert::assertInstanceOf(
            ProviderTestCtorParamWithNoTypehintOrDefaultDependent::class,
            $obj
        );
    }

    public function testMakeInstanceInjectsRawParametersDirectly()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->define(
            InjectorTestRawCtorParams::class,
            [
                ':string' => 'string',
                ':obj'    => new stdClass(),
                ':int'    => 42,
                ':array'  => [],
                ':float'  => 9.3,
                ':bool'   => true,
                ':null'   => null,
            ]
        );

        $obj = $injector->make(InjectorTestRawCtorParams::class);
        Assert::assertIsString($obj->string);
        Assert::assertInstanceOf('stdClass', $obj->obj);
        Assert::assertIsInt($obj->int);
        Assert::assertIsArray($obj->array);
        Assert::assertIsFloat($obj->float);
        Assert::assertIsBool($obj->bool);
        Assert::assertNull($obj->null);
    }

    public function testMakeInstanceHandlesNamespacedClasses()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $obj      = $injector->make(SomeClassName::class);
        Assert::assertInstanceOf(SomeClassName::class, $obj);
    }

    public function testMakeInstanceDelegate()
    {
        $injector = new Injector(InjectorFactory::create([]));

        $callable = $this->getMockBuilder(CallableMock::class)
            ->onlyMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->once())
                 ->method('__invoke')
                 ->willReturn(new TestDependency());

        $injector->delegate(TestDependency::class, $callable);

        $obj = $injector->make(TestDependency::class);

        Assert::assertInstanceOf(TestDependency::class, $obj);
    }

    public function testMakeInstanceWithStringDelegate()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(
            stdClass::class,
            StringStdClassDelegateMock::class
        );
        $obj = $injector->make(stdClass::class);
        Assert::assertEquals(42, $obj->test);
    }

    public function testMakeInstanceThrowsExceptionIfStringDelegateClassHasNoInvokeMethod()
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(stdClass::class, StringDelegateWithNoInvokeMethod::class);
    }

    public function testMakeInstanceThrowsExceptionIfStringDelegateClassInstantiationFails()
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(
            stdClass::class,
            SomeClassThatDefinitelyDoesNotExistForReal::class
        );
    }

    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithNoDefinition()
    {
        $this->expectException(InjectionException::class);

        $injector = new Injector(InjectorFactory::create([]));
        $obj      = $injector->make(RequiresInterface::class);
    }

    public function testDefineAssignsPassedDefinition()
    {
        $injector   = new Injector(InjectorFactory::create([]));
        $definition = ['dep' => DepImplementation::class];
        $injector->define(
            RequiresInterface::class,
            $definition
        );
        Assert::assertInstanceOf(
            RequiresInterface::class,
            $injector->make(RequiresInterface::class)
        );
    }

    public function testShareStoresSharedInstanceAndReturnsCurrentInstance()
    {
        $injector        = new Injector(InjectorFactory::create([]));
        $testShare       = new stdClass();
        $testShare->test = 42;

        Assert::assertInstanceOf(
            Injector::class,
            $injector->share($testShare)
        );
        $testShare->test = 'test';
        Assert::assertEquals('test', $injector->make(stdClass::class)->test);
    }

    public function testShareMarksClassSharedOnNullObjectParameter()
    {
        $injector = new Injector(InjectorFactory::create([]));
        Assert::assertInstanceOf(
            Injector::class,
            $injector->share('SomeClass')
        );
    }

    public function testAliasAssignsValueAndReturnsCurrentInstance()
    {
        $injector = new Injector(InjectorFactory::create([]));
        Assert::assertInstanceOf(
            Injector::class,
            $injector->alias(
                DepInterface::class,
                DepImplementation::class
            )
        );
    }

    public static function provideInvalidDelegates()
    {
        return [
            [new stdClass()],
        ];
    }

    #[DataProvider('provideInvalidDelegates')]
    public function testDelegateThrowsExceptionIfDelegateIsNotCallableOrString($badDelegate)
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(TestDependency::class, $badDelegate);
    }

    public function testDelegateInstantiatesCallableClassString()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(
            MadeByDelegate::class,
            CallableDelegateClassTest::class
        );
        Assert::assertInstanceof(
            MadeByDelegate::class,
            $injector->make(MadeByDelegate::class)
        );
    }

    public function testDelegateInstantiatesCallableClassArray()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(
            MadeByDelegate::class,
            [
                CallableDelegateClassTest::class,
                '__invoke',
            ]
        );
        Assert::assertInstanceof(
            MadeByDelegate::class,
            $injector->make(MadeByDelegate::class)
        );
    }

    public function testUnknownDelegationFunction()
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(InjectorFactory::create([]));
        try {
            $injector->delegate(DelegatableInterface::class, 'FunctionWhichDoesNotExist');
            $this->fail("Delegation was supposed to fail.");
        } catch (InjectionException $ie) {
            Assert::assertStringContainsString('FunctionWhichDoesNotExist', $ie->getMessage());
            Assert::assertEquals(
                InjectionException::E_DELEGATE_ARGUMENT,
                $ie->getCode()
            );
        }
    }

    public function testUnknownDelegationMethod()
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(InjectorFactory::create([]));
        try {
            $injector->delegate(
                DelegatableInterface::class,
                ['stdClass', 'methodWhichDoesNotExist']
            );
            $this->fail("Delegation was supposed to fail.");
        } catch (InjectionException $ie) {
            Assert::assertStringContainsString('stdClass', $ie->getMessage());
            Assert::assertStringContainsString('methodWhichDoesNotExist', $ie->getMessage());
            Assert::assertEquals(InjectionException::E_DELEGATE_ARGUMENT, $ie->getCode());
        }
    }

    #[DataProvider('provideExecutionExpectations')]
    public function testProvisionedInvokables($toInvoke, $definition, $expectedResult)
    {
        $injector = new Injector(InjectorFactory::create([]));
        Assert::assertEquals($expectedResult, $injector->execute($toInvoke, $definition));
    }

    public static function provideExecutionExpectations()
    {
        $return = [];

        // 0 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            ExecuteClassNoDeps::class,
            'execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 1 -------------------------------------------------------------------------------------->

        $toInvoke       = [new ExecuteClassNoDeps(), 'execute'];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 2 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            ExecuteClassDeps::class,
            'execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 3 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            new ExecuteClassDeps(new TestDependency()),
            'execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 4 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            ExecuteClassDepsWithMethodDeps::class,
            'execute',
        ];
        $args           = [':arg' => 9382];
        $expectedResult = 9382;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 5 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            ExecuteClassStaticMethod::class,
            'execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 6 -------------------------------------------------------------------------------------->

        $toInvoke       = [new ExecuteClassStaticMethod(), 'execute'];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 7 -------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Injector\Test\Fixture\ExecuteClassStaticMethod::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 8 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            ExecuteClassRelativeStaticMethod::class,
            'parent::execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 9 -------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Injector\Test\testExecuteFunction';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 10 ------------------------------------------------------------------------------------->

        $toInvoke       = function () {
            return 42;
        };
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 11 ------------------------------------------------------------------------------------->

        $toInvoke       = new ExecuteClassInvokable();
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 12 ------------------------------------------------------------------------------------->

        $toInvoke       = ExecuteClassInvokable::class;
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 13 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Injector\Test\Fixture\ExecuteClassNoDeps::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 14 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Injector\Test\Fixture\ExecuteClassDeps::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 15 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Injector\Test\Fixture\ExecuteClassStaticMethod::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 16 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Injector\Test\Fixture\ExecuteClassRelativeStaticMethod::parent::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 17 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Injector\Test\testExecuteFunctionWithArg';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 18 ------------------------------------------------------------------------------------->

        $toInvoke       = function () {
            return 42;
        };
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        if (PHP_VERSION_ID > 50400) {
            // 19 ------------------------------------------------------------------------------------->

            $object         = new ReturnsCallable('new value');
            $args           = [];
            $toInvoke       = $object->getCallable();
            $expectedResult = 'new value';
            $return[]       = [$toInvoke, $args, $expectedResult];
        }

        // x -------------------------------------------------------------------------------------->

        return $return;
    }

    public function testStaticStringInvokableWithArgument()
    {
        $injector  = new Injector(InjectorFactory::create([]));
        $invokable = $injector->buildExecutable('Qubus\Injector\Test\Fixture\ClassWithStaticMethodThatTakesArg::doSomething');
        Assert::assertEquals(42, $invokable(41));
    }

    public function testInterfaceFactoryDelegation()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(
            DelegatableInterface::class,
            ImplementsInterfaceFactory::class
        );
        $requiresDelegatedInterface = $injector->make(RequiresDelegatedInterface::class);
        $requiresDelegatedInterface->foo();
        Assert::assertTrue(true);
    }

    public function testMissingAlias()
    {
        $this->expectException(InjectionException::class);

        $injector  = new Injector(InjectorFactory::create([]));
        $testClass = $injector->make(TestMissingDependency::class);
    }

    public function testAliasingConcreteClasses()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(ConcreteClass1::class, ConcreteClass2::class);
        $obj = $injector->make(ConcreteClass1::class);
        Assert::assertInstanceOf(ConcreteClass2::class, $obj);
    }

    public function testSharedByAliasedInterfaceName()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            SharedAliasedInterface::class,
            SharedClass::class
        );
        $injector->share(SharedAliasedInterface::class);
        $class  = $injector->make(SharedAliasedInterface::class);
        $class2 = $injector->make(SharedAliasedInterface::class);
        Assert::assertSame($class, $class2);
    }

    public function testNotSharedByAliasedInterfaceName()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            SharedAliasedInterface::class,
            SharedClass::class
        );
        $injector->alias(
            SharedAliasedInterface::class,
            NotSharedClass::class
        );
        $injector->share(SharedClass::class);
        $class  = $injector->make(SharedAliasedInterface::class);
        $class2 = $injector->make(SharedAliasedInterface::class);

        Assert::assertNotSame($class, $class2);
    }

    public function testSharedByAliasedInterfaceNameReversedOrder()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(SharedAliasedInterface::class);
        $injector->alias(
            SharedAliasedInterface::class,
            SharedClass::class
        );
        $class  = $injector->make(SharedAliasedInterface::class);
        $class2 = $injector->make(SharedAliasedInterface::class);
        Assert::assertSame($class, $class2);
    }

    public function testSharedByAliasedInterfaceNameWithParameter()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            SharedAliasedInterface::class,
            SharedClass::class
        );
        $injector->share(SharedAliasedInterface::class);
        $sharedClass = $injector->make(SharedAliasedInterface::class);
        $childClass  = $injector->make(ClassWithAliasAsParameter::class);
        Assert::assertSame($sharedClass, $childClass->sharedClass);
    }

    public function testSharedByAliasedInstance()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            SharedAliasedInterface::class,
            SharedClass::class
        );
        $sharedClass = $injector->make(SharedAliasedInterface::class);
        $injector->share($sharedClass);
        $childClass = $injector->make(ClassWithAliasAsParameter::class);
        Assert::assertSame($sharedClass, $childClass->sharedClass);
    }

    public function testMultipleShareCallsDontOverrideTheOriginalSharedInstance()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(stdClass::class);
        $stdClass1 = $injector->make(stdClass::class);
        $injector->share(stdClass::class);
        $stdClass2 = $injector->make(stdClass::class);
        Assert::assertSame($stdClass1, $stdClass2);
    }

    public function testDependencyWhereSharedWithProtectedConstructor()
    {
        $injector = new Injector(InjectorFactory::create([]));

        $inner = TestDependencyWithProtectedConstructor::create();
        $injector->share($inner);

        $outer = $injector->make(TestNeedsDepWithProtCons::class);

        Assert::assertSame($inner, $outer->dep);
    }

    public function testDependencyWhereShared()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(ClassInnerB::class);
        $innerDep = $injector->make(ClassInnerB::class);
        $inner    = $injector->make(ClassInnerA::class);
        Assert::assertSame($innerDep, $inner->dep);
        $outer = $injector->make(ClassOuter::class);
        Assert::assertSame($innerDep, $outer->dep->dep);
    }

    public function testBugWithReflectionPoolIncorrectlyReturningBadInfo()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $obj      = $injector->make(ClassOuter::class);
        Assert::assertInstanceOf(ClassOuter::class, $obj);
        Assert::assertInstanceOf(ClassInnerA::class, $obj->dep);
        Assert::assertInstanceOf(ClassInnerB::class, $obj->dep->dep);
    }

    public static function provideCyclicDependencies()
    {
        return [
            RecursiveClassA::class => [RecursiveClassA::class],
            RecursiveClassB::class => [RecursiveClassB::class],
            RecursiveClassC::class => [RecursiveClassC::class],
            RecursiveClass1::class => [RecursiveClass1::class],
            RecursiveClass2::class => [RecursiveClass2::class],
            DependsOnCyclic::class => [DependsOnCyclic::class],
        ];
    }

    #[DataProvider('provideCyclicDependencies')]
    public function testCyclicDependencies($class)
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_CYCLIC_DEPENDENCY);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->make($class);
    }

    public function testNonConcreteDependencyWithDefault()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $class    = $injector->make(NonConcreteDependencyWithDefaultValue::class);
        Assert::assertInstanceOf(NonConcreteDependencyWithDefaultValue::class, $class);
        Assert::assertNull($class->interface);
    }

    public function testNonConcreteDependencyWithDefaultValueThroughAlias()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            DelegatableInterface::class,
            ImplementsInterface::class
        );
        $class = $injector->make(NonConcreteDependencyWithDefaultValue::class);
        Assert::assertInstanceOf(NonConcreteDependencyWithDefaultValue::class, $class);
        Assert::assertInstanceOf(ImplementsInterface::class, $class->interface);
    }

    public function testNonConcreteDependencyWithDefaultValueThroughDelegation()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(
            DelegatableInterface::class,
            ImplementsInterfaceFactory::class
        );
        $class = $injector->make(NonConcreteDependencyWithDefaultValue::class);
        Assert::assertInstanceOf(NonConcreteDependencyWithDefaultValue::class, $class);
        Assert::assertInstanceOf(ImplementsInterface::class, $class->interface);
    }

    public function testDependencyWithDefaultValueThroughShare()
    {
        $injector = new Injector(InjectorFactory::create([]));
        //Instance is not shared, null default is used for dependency
        $instance = $injector->make(ConcreteDependencyWithDefaultValue::class);
        Assert::assertNull($instance->dependency);

        //Instance is explicitly shared, $instance is used for dependency
        $instance = new stdClass();
        $injector->share($instance);
        $instance = $injector->make(ConcreteDependencyWithDefaultValue::class);
        Assert::assertInstanceOf(stdClass::class, $instance->dependency);
    }

    public function testShareAfterAliasException()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode(InjectorException::E_ALIASED_CANNOT_SHARE);

        $injector  = new Injector(InjectorFactory::create([]));
        $testClass = new stdClass();
        $injector->alias(stdClass::class, SomeOtherClass::class);
        $injector->share($testClass);
    }

    public function testShareAfterAliasAliasedClassAllowed()
    {
        $injector  = new Injector(InjectorFactory::create([]));
        $testClass = new DepImplementation();
        $injector->alias(DepInterface::class, DepImplementation::class);
        $injector->share($testClass);
        $obj = $injector->make(DepInterface::class);
        Assert::assertInstanceOf(DepImplementation::class, $obj);
    }

    public function testAliasAfterShareByStringAllowed()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(DepInterface::class);
        $injector->alias(DepInterface::class, DepImplementation::class);
        $obj  = $injector->make(DepInterface::class);
        $obj2 = $injector->make(DepInterface::class);
        Assert::assertInstanceOf(DepImplementation::class, $obj);
        Assert::assertEquals($obj, $obj2);
    }

    public function testAliasAfterShareBySharingAliasAllowed()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(DepImplementation::class);
        $injector->alias(DepInterface::class, DepImplementation::class);
        $obj  = $injector->make(DepInterface::class);
        $obj2 = $injector->make(DepInterface::class);
        Assert::assertInstanceOf(DepImplementation::class, $obj);
        Assert::assertEquals($obj, $obj2);
    }

    public function testAliasAfterShareException()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode(InjectorException::E_SHARED_CANNOT_ALIAS);

        $injector  = new Injector(InjectorFactory::create([]));
        $testClass = new stdClass();
        $injector->share($testClass);
        $injector->alias('stdClass', SomeOtherClass::class);
    }

    public function testAppropriateExceptionThrownOnNonPublicConstructor()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_NON_PUBLIC_CONSTRUCTOR);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->make(HasNonPublicConstructor::class);
    }

    public function testAppropriateExceptionThrownOnNonPublicConstructorWithArgs()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_NON_PUBLIC_CONSTRUCTOR);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->make(HasNonPublicConstructorWithArgs::class);
    }

    public function testMakeExecutableFailsOnNonExistentFunction()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $this->expectException(
            InjectionException::class,
            'nonExistentFunction',
            InjectorException::E_INVOKABLE
        );
        $injector->buildExecutable('nonExistentFunction');
    }

    public function testMakeExecutableFailsOnNonExistentInstanceMethod()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $object   = new stdClass();
        $this->expectException(
            InjectionException::class,
            "[object(stdClass), 'nonExistentMethod']",
            InjectorException::E_INVOKABLE
        );
        $injector->buildExecutable([$object, 'nonExistentMethod']);
    }

    public function testMakeExecutableFailsOnNonExistentStaticMethod()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $this->expectException(
            InjectionException::class,
            "stdClass::nonExistentMethod",
            InjectorException::E_INVOKABLE
        );
        $injector->buildExecutable(['stdClass', 'nonExistentMethod']);
    }

    public function testMakeExecutableFailsOnClassWithoutInvoke()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_INVOKABLE);

        $injector = new Injector(InjectorFactory::create([]));
        $object   = new stdClass();
        $injector->buildExecutable($object);
    }

    public function testBadAlias()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode(InjectorException::E_NON_EMPTY_STRING_ALIAS);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(DepInterface::class);
        $injector->alias(DepInterface::class, '');
    }

    public function testShareNewAlias()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(DepImplementation::class);
        $injector->alias(DepInterface::class, DepImplementation::class);
        Assert::assertTrue(true);
    }

    public function testDefineWithBackslashAndMakeWithoutBackslash()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->define(SimpleNoTypehintClass::class, [':arg' => 'tested']);
        $testClass = $injector->make(SimpleNoTypehintClass::class);
        Assert::assertEquals('tested', $testClass->testParam);
    }

    public function testShareWithBackslashAndMakeWithoutBackslash()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(stdClass::class);
        $classA         = $injector->make(stdClass::class);
        $classA->tested = false;
        $classB         = $injector->make(stdClass::class);
        $classB->tested = true;

        Assert::assertEquals($classA->tested, $classB->tested);
    }

    public function testInstanceMutate()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->prepare(stdClass::class, function ($obj, $injector) {
            $obj->testval = 42;
        });
        $obj = $injector->make(stdClass::class);

        Assert::assertSame(42, $obj->testval);
    }

    public function testInterfaceMutate()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->prepare(
            SomeInterface::class,
            function ($obj, $injector) {
                $obj->testProp = 42;
            }
        );
        $obj = $injector->make(PreparesImplementationTest::class);

        Assert::assertSame(42, $obj->testProp);
    }

    /**
     * Test that custom definitions are not passed through to dependencies.
     * Surprising things would happen if this did occur.
     */
    public function testCustomDefinitionNotPassedThrough()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->share(DependencyWithDefinedParam::class);
        $injector->make(RequiresDependencyWithDefinedParam::class, [':foo' => 5]);
    }

    public function testDelegationFunction()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(
            TestDelegationSimple::class,
            'Qubus\Injector\Test\createTestDelegationSimple'
        );
        $obj = $injector->make(TestDelegationSimple::class);
        Assert::assertInstanceOf(TestDelegationSimple::class, $obj);
        Assert::assertTrue($obj->delegateCalled);
    }

    public function testDelegationDependency()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(
            TestDelegationDependency::class,
            createTestDelegationDependency::class
        );
        $obj = $injector->make(TestDelegationDependency::class);
        Assert::assertInstanceOf(TestDelegationDependency::class, $obj);
        Assert::assertTrue($obj->delegateCalled);
    }

    public function testExecutableAliasing()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            BaseExecutableClass::class,
            ExtendsExecutableClass::class
        );
        $result = $injector->execute([
            BaseExecutableClass::class,
            'foo',
        ]);
        Assert::assertEquals('This is the ExtendsExecutableClass', $result);
    }

    public function testExecutableAliasingStatic()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->alias(
            BaseExecutableClass::class,
            ExtendsExecutableClass::class
        );
        $result = $injector->execute([
            BaseExecutableClass::class,
            'bar',
        ]);
        Assert::assertEquals('This is the ExtendsExecutableClass', $result);
    }

    /**
     * Test coverage for delegate closures that are defined outside
     * of a class.ph
     *
     * @throws ConfigException
     */
    public function testDelegateClosure()
    {
        $delegateClosure = \Qubus\Injector\Test\getDelegateClosureInGlobalScope();
        $injector        = new Injector(InjectorFactory::create([]));
        $injector->delegate(DelegateClosureInGlobalScope::class, $delegateClosure);
        $obj = $injector->make(DelegateClosureInGlobalScope::class);
        Assert::assertInstanceOf(DelegateClosureInGlobalScope::class, $obj);
    }

    public function testCloningWithServiceLocator()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $injector->share($injector);
        $instance    = $injector->make(CloneInjector::class);
        $newInjector = $instance->injector;
        $newInstance = $newInjector->make(CloneInjector::class);
        Assert::assertInstanceOf(CloneInjector::class, $instance);
        Assert::assertInstanceOf(CloneInjector::class, $newInstance);
    }

    public function testAbstractExecute()
    {
        $injector = new Injector(InjectorFactory::create([]));

        $fn = fn () => new ConcreteExecute();

        $injector->delegate(BaseExecute::class, $fn);
        $result = $injector->execute([
            BaseExecute::class,
            'process',
        ]);

        Assert::assertEquals('Concrete', $result);
    }

    public function testDelegationDoesntMakeObject()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_MAKING_FAILED);

        $delegate = function () {
            return null;
        };
        $injector = new Injector(InjectorFactory::create([]));
        $injector->delegate(SomeClassName::class, $delegate);
        $injector->make(SomeClassName::class);
    }

    public function testPrepareCallableReplacesObjectWithReturnValueOfSameInterfaceType()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $expected = new SomeImplementation(); // <-- implements SomeInterface
        $injector->prepare(
            SomeInterface::class,
            function ($impl) use ($expected) {
                return $expected;
            }
        );
        $actual = $injector->make(SomeImplementation::class);
        Assert::assertSame($expected, $actual);
    }

    public function testPrepareCallableReplacesObjectWithReturnValueOfSameClassType()
    {
        $injector = new Injector(InjectorFactory::create([]));
        $expected = new SomeImplementation(); // <-- implements SomeInterface
        $injector->prepare(
            SomeImplementation::class,
            function ($impl) use ($expected) {
                return $expected;
            }
        );
        $actual = $injector->make(SomeImplementation::class);
        Assert::assertSame($expected, $actual);
    }

    public function testChildWithoutConstructorWorks()
    {
        $injector = new Injector(InjectorFactory::create([]));
        try {
            $injector->define(ParentWithConstructor::class, [':foo' => 'parent']);
            $injector->define(ChildWithoutConstructor::class, [':foo' => 'child']);

            $injector->share(ParentWithConstructor::class);
            $injector->share(ChildWithoutConstructor::class);

            $child = $injector->make(ChildWithoutConstructor::class);
            Assert::assertEquals('child', $child->foo);

            $parent = $injector->make(ParentWithConstructor::class);
            Assert::assertEquals('parent', $parent->foo);
        } catch (InjectionException $ie) {
            echo $ie->getMessage();
            $this->fail('Auryn failed to locate the ');
        }
    }

    public function testChildWithoutConstructorMissingParam()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(InjectorFactory::create([]));
        $injector->define(ParentWithConstructor::class, [':foo' => 'parent']);
        $injector->make(ChildWithoutConstructor::class);
    }
}

function testExecuteFunction()
{
    return 42;
}

function testExecuteFunctionWithArg(ConcreteClass1 $foo)
{
    return 42;
}

function createTestDelegationSimple()
{
    $instance                 = new TestDelegationSimple();
    $instance->delegateCalled = true;

    return $instance;
}

function createTestDelegationDependency(TestDelegationSimple $testDelegationSimple)
{
    $instance                 = new TestDelegationDependency($testDelegationSimple);
    $instance->delegateCalled = true;

    return $instance;
}

function getDelegateClosureInGlobalScope(): Closure
{
    return function () {
        return new DelegateClosureInGlobalScope();
    };
}
