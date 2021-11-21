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

namespace Qubus\Tests\Injector;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Injector\Config\Config;
use Qubus\Injector\Config\Factory;
use Qubus\Injector\ConfigException;
use Qubus\Injector\InjectionChain;
use Qubus\Injector\InjectionException;
use Qubus\Injector\Injector;
use Qubus\Injector\InjectorException;
use Qubus\Injector\Psr11\Container;
use Qubus\Injector\ServiceContainer;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;
use stdClass;
use TypeError;

use const PHP_VERSION_ID;

class InjectorTest extends TestCase
{
    public function testMakeInstancesThroughConfigAlias()
    {
        $injector = new Injector(Factory::create([
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
        $injector = new Injector(Factory::create([
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
        $injector = new Injector(Factory::create([
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
        $injector = new Injector(Factory::create([
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
        $injector = new Injector(Factory::create([]));
        Assert::assertEquals(
            new TestNeedsDep(new TestDependency()),
            $injector->make(TestNeedsDep::class)
        );
    }

    public function testMakeInstanceReturnsNewInstanceIfClassHasNoConstructor()
    {
        $injector = new Injector(Factory::create([]));
        Assert::assertEquals(
            new TestNoConstructor(),
            $injector->make(TestNoConstructor::class)
        );
    }

    public function testMakeInstanceReturnsAliasInstanceOnNonConcreteTypehint()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            DepInterface::class,
            DepImplementation::class
        );
        Assert::assertEquals(
            new DepImplementation(),
            $injector->make(DepInterface::class)
        );
    }

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_NEEDS_DEFINITION
     */
    public function testMakeInstanceThrowsExceptionOnInterfaceWithoutAlias()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_NEEDS_DEFINITION);

        $injector = new Injector(Factory::create([]));
        $injector->make(DepInterface::class);
    }

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_NEEDS_DEFINITION
     */
    public function testMakeInstanceThrowsExceptionOnNonConcreteCtorParamWithoutImplementation()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_NEEDS_DEFINITION);

        $injector = new Injector(Factory::create([]));
        $injector->make(RequiresInterface::class);
    }

    public function testMakeInstanceBuildsNonConcreteCtorParamWithAlias()
    {
        $injector = new Injector(Factory::create([]));
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
        $injector         = new Injector(Factory::create([]));
        $nullCtorParamObj = $injector->make(ProvTestNoDefinitionNullDefaultClass::class);
        Assert::assertEquals(new ProvTestNoDefinitionNullDefaultClass(), $nullCtorParamObj);
        Assert::assertEquals(null, $nullCtorParamObj->arg);
    }

    public function testMakeInstanceReturnsSharedInstanceIfAvailable()
    {
        $injector = new Injector(Factory::create([]));
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

    /**
     * @expectedException InjectorException
     */
    public function testMakeInstanceThrowsExceptionOnClassLoadFailure()
    {
        $this->expectException(InjectionException::class);

        $injector = new Injector(Factory::create([]));
        $injector->make('ClassThatDoesntExist');
    }

    public function testMakeInstanceUsesCustomDefinitionIfSpecified()
    {
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
        $injector->share(TestDependency::class);
        $obj = $injector->make(TestDependency::class);
        Assert::assertInstanceOf(TestDependency::class, $obj);
    }

    public function testMakeInstanceUsesReflectionForUnknownParamsInMultiBuildWithDeps()
    {
        $injector = new Injector(Factory::create([]));
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

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_UNDEFINED_PARAM
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefault()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make(InjectorTestCtorParamWithNoTypehintOrDefault::class);
        Assert::assertNull($obj->val);
    }

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_UNDEFINED_PARAM
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefaultThroughAliasedTypehint()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(Factory::create([]));
        $injector->alias(
            TestNoExplicitDefine::class,
            InjectorTestCtorParamWithNoTypehintOrDefault::class
        );
        $injector->make(InjectorTestCtorParamWithNoTypehintOrDefaultDependent::class);
    }

    /**
     * @todo
     * @expectedException InjectorException
     */
    public function testMakeInstanceThrowsExceptionOnUninstantiableTypehintWithoutDefinition()
    {
        $this->expectException(InjectionException::class);

        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make(RequiresInterface::class);
    }

    public function testTypelessDefineForDependency()
    {
        $thumbnailSize = 128;
        $injector      = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make(SomeClassName::class);
        Assert::assertInstanceOf(SomeClassName::class, $obj);
    }

    public function testMakeInstanceDelegate()
    {
        $injector = new Injector(Factory::create([]));

        $callable = $this->getMockBuilder('CallableMock')
            ->setMethods(['__invoke'])
            ->getMock();

        $callable->expects($this->once())
                 ->method('__invoke')
                 ->will($this->returnValue(new TestDependency()));

        $injector->delegate(TestDependency::class, $callable);

        $obj = $injector->make(TestDependency::class);

        Assert::assertInstanceOf(TestDependency::class, $obj);
    }

    public function testMakeInstanceWithStringDelegate()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            stdClass::class,
            StringStdClassDelegateMock::class
        );
        $obj = $injector->make(stdClass::class);
        Assert::assertEquals(42, $obj->test);
    }

    /**
     * @expectedException ConfigException
     */
    public function testMakeInstanceThrowsExceptionIfStringDelegateClassHasNoInvokeMethod()
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(Factory::create([]));
        $injector->delegate(stdClass::class, StringDelegateWithNoInvokeMethod::class);
    }

    /**
     * @expectedException ConfigException
     */
    public function testMakeInstanceThrowsExceptionIfStringDelegateClassInstantiationFails()
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            stdClass::class,
            SomeClassThatDefinitelyDoesNotExistForReal::class
        );
    }

    /**
     * @expectedException InjectionException
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithNoDefinition()
    {
        $this->expectException(InjectionException::class);

        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make(RequiresInterface::class);
    }

    public function testDefineAssignsPassedDefinition()
    {
        $injector   = new Injector(Factory::create([]));
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
        $injector        = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
        Assert::assertInstanceOf(
            Injector::class,
            $injector->share('SomeClass')
        );
    }

    public function testAliasAssignsValueAndReturnsCurrentInstance()
    {
        $injector = new Injector(Factory::create([]));
        Assert::assertInstanceOf(
            Injector::class,
            $injector->alias(
                DepInterface::class,
                DepImplementation::class
            )
        );
    }

    public function provideInvalidDelegates()
    {
        return [
            [new stdClass()],
            [42],
            [true],
        ];
    }

    /**
     * @dataProvider provideInvalidDelegates
     * @expectedException ConfigException
     */
    public function testDelegateThrowsExceptionIfDelegateIsNotCallableOrString($badDelegate)
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(Factory::create([]));
        $injector->delegate(TestDependency::class, $badDelegate);
    }

    public function testDelegateInstantiatesCallableClassString()
    {
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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

        $injector = new Injector(Factory::create([]));
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

        $injector = new Injector(Factory::create([]));
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

    /**
     * @dataProvider provideExecutionExpectations
     */
    public function testProvisionedInvokables($toInvoke, $definition, $expectedResult)
    {
        $injector = new Injector(Factory::create([]));
        Assert::assertEquals($expectedResult, $injector->execute($toInvoke, $definition));
    }

    public function provideExecutionExpectations()
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

        $toInvoke       = 'Qubus\Tests\Injector\ExecuteClassStaticMethod::execute';
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

        $toInvoke       = 'Qubus\Tests\Injector\testExecuteFunction';
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

        $toInvoke       = 'Qubus\Tests\Injector\ExecuteClassNoDeps::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 14 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Tests\Injector\ExecuteClassDeps::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 15 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Tests\Injector\ExecuteClassStaticMethod::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 16 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Tests\Injector\ExecuteClassRelativeStaticMethod::parent::execute';
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 17 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Tests\Injector\testExecuteFunctionWithArg';
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
        $injector  = new Injector(Factory::create([]));
        $invokable = $injector->buildExecutable('Qubus\Tests\Injector\ClassWithStaticMethodThatTakesArg::doSomething');
        Assert::assertEquals(42, $invokable(41));
    }

    public function testInterfaceFactoryDelegation()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            DelegatableInterface::class,
            ImplementsInterfaceFactory::class
        );
        $requiresDelegatedInterface = $injector->make(RequiresDelegatedInterface::class);
        $requiresDelegatedInterface->foo();
        Assert::assertTrue(true);
    }

    /**
     * @expectedException InjectorException
     */
    public function testMissingAlias()
    {
        $this->expectException(InjectionException::class);

        $injector  = new Injector(Factory::create([]));
        $testClass = $injector->make(TestMissingDependency::class);
    }

    public function testAliasingConcreteClasses()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(ConcreteClass1::class, ConcreteClass2::class);
        $obj = $injector->make(ConcreteClass1::class);
        Assert::assertInstanceOf(ConcreteClass2::class, $obj);
    }

    public function testSharedByAliasedInterfaceName()
    {
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
        $injector->share(stdClass::class);
        $stdClass1 = $injector->make(stdClass::class);
        $injector->share(stdClass::class);
        $stdClass2 = $injector->make(stdClass::class);
        Assert::assertSame($stdClass1, $stdClass2);
    }

    public function testDependencyWhereSharedWithProtectedConstructor()
    {
        $injector = new Injector(Factory::create([]));

        $inner = TestDependencyWithProtectedConstructor::create();
        $injector->share($inner);

        $outer = $injector->make(TestNeedsDepWithProtCons::class);

        Assert::assertSame($inner, $outer->dep);
    }

    public function testDependencyWhereShared()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share(ClassInnerB::class);
        $innerDep = $injector->make(ClassInnerB::class);
        $inner    = $injector->make(ClassInnerA::class);
        Assert::assertSame($innerDep, $inner->dep);
        $outer = $injector->make(ClassOuter::class);
        Assert::assertSame($innerDep, $outer->dep->dep);
    }

    public function testBugWithReflectionPoolIncorrectlyReturningBadInfo()
    {
        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make(ClassOuter::class);
        Assert::assertInstanceOf(ClassOuter::class, $obj);
        Assert::assertInstanceOf(ClassInnerA::class, $obj->dep);
        Assert::assertInstanceOf(ClassInnerB::class, $obj->dep->dep);
    }

    public function provideCyclicDependencies()
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

    /**
     * @dataProvider provideCyclicDependencies
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_CYCLIC_DEPENDENCY
     */
    public function testCyclicDependencies($class)
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_CYCLIC_DEPENDENCY);

        $injector = new Injector(Factory::create([]));
        $injector->make($class);
    }

    public function testNonConcreteDependencyWithDefault()
    {
        $injector = new Injector(Factory::create([]));
        $class    = $injector->make(NonConcreteDependencyWithDefaultValue::class);
        Assert::assertInstanceOf(NonConcreteDependencyWithDefaultValue::class, $class);
        Assert::assertNull($class->interface);
    }

    public function testNonConcreteDependencyWithDefaultValueThroughAlias()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            DelegatableInterface::class,
            ImplementsInterface::class
        );
        $class = $injector->make(NonConcreteDependencyWithDefaultValue::class);
        Assert::assertInstanceOf(NonConcreteDependencyWithDefaultValue::class, $class);
        Assert::assertNotInstanceOf(ImplementsInterface::class, $class->interface);
    }

    public function testNonConcreteDependencyWithDefaultValueThroughDelegation()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            DelegatableInterface::class,
            ImplementsInterfaceFactory::class
        );
        $class = $injector->make(NonConcreteDependencyWithDefaultValue::class);
        Assert::assertInstanceOf(NonConcreteDependencyWithDefaultValue::class, $class);
        Assert::assertNotInstanceOf(ImplementsInterface::class, $class->interface);
    }

    public function testDependencyWithDefaultValueThroughShare()
    {
        $injector = new Injector(Factory::create([]));
        //Instance is not shared, null default is used for dependency
        $instance = $injector->make(ConcreteDependencyWithDefaultValue::class);
        Assert::assertNull($instance->dependency);

        //Instance is explicitly shared, $instance is used for dependency
        $instance = new stdClass();
        $injector->share($instance);
        $instance = $injector->make(ConcreteDependencyWithDefaultValue::class);
        Assert::assertNotInstanceOf(stdClass::class, $instance->dependency);
    }

    /**
     * @expectedException ConfigException
     * @expectedExceptionCode InjectorException::E_ALIASED_CANNOT_SHARE
     */
    public function testShareAfterAliasException()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode(InjectorException::E_ALIASED_CANNOT_SHARE);

        $injector  = new Injector(Factory::create([]));
        $testClass = new stdClass();
        $injector->alias(stdClass::class, SomeOtherClass::class);
        $injector->share($testClass);
    }

    public function testShareAfterAliasAliasedClassAllowed()
    {
        $injector  = new Injector(Factory::create([]));
        $testClass = new DepImplementation();
        $injector->alias(DepInterface::class, DepImplementation::class);
        $injector->share($testClass);
        $obj = $injector->make(DepInterface::class);
        Assert::assertInstanceOf(DepImplementation::class, $obj);
    }

    public function testAliasAfterShareByStringAllowed()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share(DepInterface::class);
        $injector->alias(DepInterface::class, DepImplementation::class);
        $obj  = $injector->make(DepInterface::class);
        $obj2 = $injector->make(DepInterface::class);
        Assert::assertInstanceOf(DepImplementation::class, $obj);
        Assert::assertEquals($obj, $obj2);
    }

    public function testAliasAfterShareBySharingAliasAllowed()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share(DepImplementation::class);
        $injector->alias(DepInterface::class, DepImplementation::class);
        $obj  = $injector->make(DepInterface::class);
        $obj2 = $injector->make(DepInterface::class);
        Assert::assertInstanceOf(DepImplementation::class, $obj);
        Assert::assertEquals($obj, $obj2);
    }

    /**
     * @expectedException ConfigException
     * @expectedExceptionCode InjectorException::E_SHARED_CANNOT_ALIAS
     */
    public function testAliasAfterShareException()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode(InjectorException::E_SHARED_CANNOT_ALIAS);

        $injector  = new Injector(Factory::create([]));
        $testClass = new stdClass();
        $injector->share($testClass);
        $injector->alias('stdClass', SomeOtherClass::class);
    }

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_NON_PUBLIC_CONSTRUCTOR
     */
    public function testAppropriateExceptionThrownOnNonPublicConstructor()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_NON_PUBLIC_CONSTRUCTOR);

        $injector = new Injector(Factory::create([]));
        $injector->make(HasNonPublicConstructor::class);
    }

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_NON_PUBLIC_CONSTRUCTOR
     */
    public function testAppropriateExceptionThrownOnNonPublicConstructorWithArgs()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_NON_PUBLIC_CONSTRUCTOR);

        $injector = new Injector(Factory::create([]));
        $injector->make(HasNonPublicConstructorWithArgs::class);
    }

    public function testMakeExecutableFailsOnNonExistentFunction()
    {
        $injector = new Injector(Factory::create([]));
        $this->expectException(
            InjectionException::class,
            'nonExistentFunction',
            InjectorException::E_INVOKABLE
        );
        $injector->buildExecutable('nonExistentFunction');
    }

    public function testMakeExecutableFailsOnNonExistentInstanceMethod()
    {
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
        $this->expectException(
            InjectionException::class,
            "stdClass::nonExistentMethod",
            InjectorException::E_INVOKABLE
        );
        $injector->buildExecutable(['stdClass', 'nonExistentMethod']);
    }

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode  InjectorException::E_INVOKABLE
     */
    public function testMakeExecutableFailsOnClassWithoutInvoke()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_INVOKABLE);

        $injector = new Injector(Factory::create([]));
        $object   = new stdClass();
        $injector->buildExecutable($object);
    }

    /**
     * @expectedException ConfigException
     * @expectedExceptionCode InjectorException::E_NON_EMPTY_STRING_ALIAS
     */
    public function testBadAlias()
    {
        $this->expectException(ConfigException::class);
        $this->expectExceptionCode(InjectorException::E_NON_EMPTY_STRING_ALIAS);

        $injector = new Injector(Factory::create([]));
        $injector->share(DepInterface::class);
        $injector->alias(DepInterface::class, '');
    }

    public function testShareNewAlias()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share(DepImplementation::class);
        $injector->alias(DepInterface::class, DepImplementation::class);
        Assert::assertTrue(true);
    }

    public function testDefineWithBackslashAndMakeWithoutBackslash()
    {
        $injector = new Injector(Factory::create([]));
        $injector->define(SimpleNoTypehintClass::class, [':arg' => 'tested']);
        $testClass = $injector->make(SimpleNoTypehintClass::class);
        Assert::assertEquals('tested', $testClass->testParam);
    }

    public function testShareWithBackslashAndMakeWithoutBackslash()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share(stdClass::class);
        $classA         = $injector->make(stdClass::class);
        $classA->tested = false;
        $classB         = $injector->make(stdClass::class);
        $classB->tested = true;

        Assert::assertEquals($classA->tested, $classB->tested);
    }

    public function testInstanceMutate()
    {
        $injector = new Injector(Factory::create([]));
        $injector->prepare(stdClass::class, function ($obj, $injector) {
            $obj->testval = 42;
        });
        $obj = $injector->make(stdClass::class);

        Assert::assertSame(42, $obj->testval);
    }

    public function testInterfaceMutate()
    {
        $injector = new Injector(Factory::create([]));
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
     *
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_UNDEFINED_PARAM
     */
    public function testCustomDefinitionNotPassedThrough()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(Factory::create([]));
        $injector->share(DependencyWithDefinedParam::class);
        $injector->make(RequiresDependencyWithDefinedParam::class, [':foo' => 5]);
    }

    public function testDelegationFunction()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            TestDelegationSimple::class,
            'Qubus\Tests\Injector\createTestDelegationSimple'
        );
        $obj = $injector->make(TestDelegationSimple::class);
        Assert::assertInstanceOf(TestDelegationSimple::class, $obj);
        Assert::assertTrue($obj->delegateCalled);
    }

    public function testDelegationDependency()
    {
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $delegateClosure = \Qubus\Tests\Injector\getDelegateClosureInGlobalScope();
        $injector        = new Injector(Factory::create([]));
        $injector->delegate(DelegateClosureInGlobalScope::class, $delegateClosure);
        $obj = $injector->make(DelegateClosureInGlobalScope::class);
        Assert::assertInstanceOf(DelegateClosureInGlobalScope::class, $obj);
    }

    public function testCloningWithServiceLocator()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share($injector);
        $instance    = $injector->make(CloneTest::class);
        $newInjector = $instance->injector;
        $newInstance = $newInjector->make(CloneTest::class);
        Assert::assertInstanceOf(CloneTest::class, $instance);
        Assert::assertInstanceOf(CloneTest::class, $newInstance);
    }

    public function testAbstractExecute()
    {
        $injector = new Injector(Factory::create([]));

        $fn = fn () => new ConcreteExecuteTest();

        $injector->delegate(AbstractExecuteTest::class, $fn);
        $result = $injector->execute([
            AbstractExecuteTest::class,
            'process',
        ]);

        Assert::assertEquals('Concrete', $result);
    }

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_MAKING_FAILED
     */
    public function testDelegationDoesntMakeObject()
    {
        $this->expectException(TypeError::class);

        $delegate = function () {
            return null;
        };
        $injector = new Injector(Factory::create([]));
        $injector->delegate(SomeClassName::class, $delegate);
        $injector->make(SomeClassName::class);
    }

    public function testPrepareCallableReplacesObjectWithReturnValueOfSameInterfaceType()
    {
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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
        $injector = new Injector(Factory::create([]));
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

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_UNDEFINED_PARAM
     */
    public function testChildWithoutConstructorMissingParam()
    {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(Factory::create([]));
        $injector->define(ParentWithConstructor::class, [':foo' => 'parent']);
        $injector->make(ChildWithoutConstructor::class);
    }

    public function testInjectionChainValue()
    {
        $fn = function (InjectionChain $ic) {
            if (
                $ic->getByIndex(-2) ===
                InjectionChainTestDependency::class
            ) {
                return new InjectionChainValue('Value for dependency');
            } elseif (
                $ic->getByIndex(-2) ===
                       InjectionChainTest::class
            ) {
                return new InjectionChainValue('Value for parent');
            }

            return new InjectionChainValue('unknown value');
        };

        $injector = new Injector(Factory::create([]));
        $injector->share($injector);
        $injector->delegate(InjectionChainValue::class, $fn);
        $injector->delegate(InjectionChain::class, [$injector, 'getInjectionChain']);

        $object = $injector->make(InjectionChainTest::class);
        Assert::assertEquals($object->icv->value, 'unknown value');
        Assert::assertEquals($object->dependency->icv->value, 'unknown value');
    }

    public function testServiceProvider()
    {
        $injector = new Container(Factory::create([]));

        $service = new FakeServiceProvider();
        $service->register($injector);

        $name = new Person('Joseph Smith');

        $injected = $injector->make('user.model');

        Assert::assertEquals($name, $injected->userName());
        Assert::assertInstanceOf(Person::class, $injected->userName());
    }
}

interface SharedAliasedInterface
{
    public function foo();
}

interface DepInterface
{
}

interface SomeInterface
{
}

interface TestNoExplicitDefine
{
}

interface DelegatableInterface
{
    public function foo();
}

class ConfigClass
{
    //use ConfigTrait;

    public function __construct(Config $config)
    {
        //$this->processConfig($config);
    }

    public function check($key)
    {
        //return $this->getConfigKey($key);
    }
}

class InaccessibleExecutableClassMethod
{
    protected function doSomethingProtected()
    {
        return 42;
    }

    private function doSomethingPrivate()
    {
        return 42;
    }
}

class InaccessibleStaticExecutableClassMethod
{
    protected static function doSomethingProtected()
    {
        return 42;
    }

    private static function doSomethingPrivate()
    {
        return 42;
    }
}

class ClassWithStaticMethodThatTakesArg
{
    public static function doSomething($arg)
    {
        return 1 + $arg;
    }
}

class RecursiveClass1
{
    public function __construct(RecursiveClass2 $dep)
    {
    }
}

class RecursiveClass2
{
    public function __construct(RecursiveClass1 $dep)
    {
    }
}

class RecursiveClassA
{
    public function __construct(RecursiveClassB $b)
    {
    }
}

class RecursiveClassB
{
    public function __construct(RecursiveClassC $c)
    {
    }
}

class RecursiveClassC
{
    public function __construct(RecursiveClassA $a)
    {
    }
}

class DependsOnCyclic
{
    public function __construct(RecursiveClassA $a)
    {
    }
}

class SharedClass implements SharedAliasedInterface
{
    public function foo()
    {
    }
}

class NotSharedClass implements SharedAliasedInterface
{
    public function foo()
    {
    }
}

class DependencyWithDefinedParam
{
    public $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }
}

class RequiresDependencyWithDefinedParam
{
    public $obj;

    public function __construct(DependencyWithDefinedParam $obj)
    {
        $this->obj = $obj;
    }
}

class ClassWithAliasAsParameter
{
    public $sharedClass;

    public function __construct(SharedClass $sharedClass)
    {
        $this->sharedClass = $sharedClass;
    }
}

class ConcreteClass1
{
}

class ConcreteClass2
{
}

class ClassWithoutMagicInvoke
{
}

class TestNoConstructor
{
}

class TestDependency
{
    public $testProp = 'testVal';
}

class TestDependency2 extends TestDependency
{
    public $testProp = 'testVal2';
}

class SpecdTestDependency extends TestDependency
{
    public $testProp = 'testVal';
}

class TestNeedsDep
{
    public function __construct(TestDependency $testDep)
    {
        $this->testDep = $testDep;
    }
}

class TestClassWithNoCtorTypehints
{
    public function __construct($val = 42)
    {
        $this->test = $val;
    }
}

class TestMultiDepsNeeded
{
    public function __construct(TestDependency $val1, TestDependency2 $val2)
    {
        $this->testDep = $val1;
        $this->testDep = $val2;
    }
}

class TestMultiDepsWithCtor
{
    public function __construct(TestDependency $val1, TestNeedsDep $val2)
    {
        $this->testDep = $val1;
        $this->testDep = $val2;
    }
}

class NoTypehintNullDefaultConstructorClass
{
    public $testParam = 1;

    public function __construct(TestDependency $val1, $arg = 42)
    {
        $this->testParam = $arg;
    }
}

class NoTypehintNoDefaultConstructorClass
{
    public $testParam = 1;

    public function __construct(TestDependency $val1, $arg = null)
    {
        $this->testParam = $arg;
    }
}

class SomeImplementation implements SomeInterface
{
}

class PreparesImplementationTest implements SomeInterface
{
    public $testProp = 0;
}

class DepImplementation implements DepInterface
{
    public $testProp = 'something';
}

class RequiresInterface
{
    public $dep;

    public function __construct(DepInterface $dep)
    {
        $this->testDep = $dep;
    }
}

class ClassInnerA
{
    public $dep;

    public function __construct(ClassInnerB $dep)
    {
        $this->dep = $dep;
    }
}

class ClassInnerB
{
    public function __construct()
    {
    }
}

class ClassOuter
{
    public $dep;

    public function __construct(ClassInnerA $dep)
    {
        $this->dep = $dep;
    }
}

class ProvTestNoDefinitionNullDefaultClass
{
    public function __construct($arg = null)
    {
        $this->arg = $arg;
    }
}

class InjectorTestCtorParamWithNoTypehintOrDefault implements TestNoExplicitDefine
{
    public $val = 42;

    public function __construct($val)
    {
        $this->val = $val;
    }
}

class InjectorTestCtorParamWithNoTypehintOrDefaultDependent
{
    private $param;

    public function __construct(TestNoExplicitDefine $param)
    {
        $this->param = $param;
    }
}

class InjectorTestRawCtorParams
{
    public $string;
    public $obj;
    public $int;
    public $array;
    public $float;
    public $bool;
    public $null;

    public function __construct($string, $obj, $int, $array, $float, $bool, $null)
    {
        $this->string = $string;
        $this->obj    = $obj;
        $this->int    = $int;
        $this->array  = $array;
        $this->float  = $float;
        $this->bool   = $bool;
        $this->null   = $null;
    }
}

class InjectorTestParentClass
{
    public function __construct($arg1)
    {
        $this->arg1 = $arg1;
    }
}

class InjectorTestChildClass extends InjectorTestParentClass
{
    public function __construct($arg1, $arg2)
    {
        parent::__construct($arg1);
        $this->arg2 = $arg2;
    }
}

class CallableMock
{
    public function __invoke()
    {
    }
}

class ProviderTestCtorParamWithNoTypehintOrDefault implements TestNoExplicitDefine
{
    public $val = 42;

    public function __construct($val)
    {
        $this->val = $val;
    }
}

class ProviderTestCtorParamWithNoTypehintOrDefaultDependent
{
    private $param;

    public function __construct(TestNoExplicitDefine $param)
    {
        $this->param = $param;
    }
}

class StringStdClassDelegateMock
{
    public function __invoke()
    {
        return $this->make();
    }

    private function make()
    {
        $obj       = new stdClass();
        $obj->test = 42;

        return $obj;
    }
}

class StringDelegateWithNoInvokeMethod
{
}

class ExecuteClassNoDeps
{
    public function execute()
    {
        return 42;
    }
}

class ExecuteClassDeps
{
    public function __construct(TestDependency $testDep)
    {
    }

    public function execute()
    {
        return 42;
    }
}

class ExecuteClassDepsWithMethodDeps
{
    public function __construct(TestDependency $testDep)
    {
    }

    public function execute(TestDependency $dep, $arg = null)
    {
        return $arg ?? 42;
    }
}

class ExecuteClassStaticMethod
{
    public static function execute()
    {
        return 42;
    }
}

class ExecuteClassRelativeStaticMethod extends ExecuteClassStaticMethod
{
    public static function execute()
    {
        return 'this should NEVER be seen since we are testing against parent::execute()';
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

class ExecuteClassInvokable
{
    public function __invoke()
    {
        return 42;
    }
}

class MadeByDelegate
{
}

class CallableDelegateClassTest
{
    public function __invoke()
    {
        return new MadeByDelegate();
    }
}

class ImplementsInterface implements DelegatableInterface
{
    public function foo()
    {
    }
}

class ImplementsInterfaceFactory
{
    public function __invoke()
    {
        return new ImplementsInterface();
    }
}

class RequiresDelegatedInterface
{
    private $interface;

    public function __construct(DelegatableInterface $interface)
    {
        $this->interface = $interface;
    }

    public function foo()
    {
        $this->interface->foo();
    }
}

class TestMissingDependency
{
    public function __construct(TypoInTypehint $class)
    {
    }
}

class NonConcreteDependencyWithDefaultValue
{
    public $interface;

    public function __construct(?DelegatableInterface $i = null)
    {
        $this->interface = $i;
    }
}

class ConcreteDependencyWithDefaultValue
{
    public $dependency;

    public function __construct(?stdClass $instance = null)
    {
        $this->dependency = $instance;
    }
}

class TypelessParameterDependency
{
    public $thumbnailSize;

    public function __construct($thumbnailSize)
    {
        $this->thumbnailSize = $thumbnailSize;
    }
}

class RequiresDependencyWithTypelessParameters
{
    public $dependency;

    public function __construct(TypelessParameterDependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getThumbnailSize()
    {
        return $this->dependency->thumbnailSize;
    }
}

class HasNonPublicConstructor
{
    protected function __construct()
    {
    }
}

class HasNonPublicConstructorWithArgs
{
    protected function __construct($arg1, $arg2, $arg3)
    {
    }
}

class ClassWithCtor
{
    public function __construct()
    {
    }
}

class TestDependencyWithProtectedConstructor
{
    protected function __construct()
    {
    }

    public static function create()
    {
        return new self();
    }
}

class TestNeedsDepWithProtCons
{
    public function __construct(TestDependencyWithProtectedConstructor $dep)
    {
        $this->dep = $dep;
    }
}

class SimpleNoTypehintClass
{
    public $testParam = 1;

    public function __construct($arg)
    {
        $this->testParam = $arg;
    }
}

class SomeClassName
{
}

class TestDelegationSimple
{
    public $delgateCalled = false;
}

class TestDelegationDependency
{
    public $delgateCalled = false;

    public function __construct(TestDelegationSimple $testDelegationSimple)
    {
    }
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

class BaseExecutableClass
{
    public static function bar()
    {
        return 'This is the BaseExecutableClass';
    }

    public function foo()
    {
        return 'This is the BaseExecutableClass';
    }
}

class ExtendsExecutableClass extends BaseExecutableClass
{
    public static function bar()
    {
        return 'This is the ExtendsExecutableClass';
    }

    public function foo()
    {
        return 'This is the ExtendsExecutableClass';
    }
}

class ReturnsCallable
{
    private $value = 'original';

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getCallable()
    {
        return function () {
            return $this->value;
        };
    }
}

class DelegateClosureInGlobalScope
{
}

function getDelegateClosureInGlobalScope()
{
    return function () {
        return new DelegateClosureInGlobalScope();
    };
}

class CloneTest
{
    public $injector;

    public function __construct(Injector $injector)
    {
        $this->injector = clone $injector;
    }
}

abstract class AbstractExecuteTest
{
    public function process()
    {
        return "Abstract";
    }
}

class ConcreteExecuteTest extends AbstractExecuteTest
{
    public function process()
    {
        return "Concrete";
    }
}

class DependencyChainTest
{
    public function __construct(DepInterface $dep)
    {
    }
}

class ParentWithConstructor
{
    public $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }
}

class ChildWithoutConstructor extends ParentWithConstructor
{
}

class InjectionChainValue
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}

class InjectionChainTestDependency
{
    public $icv;

    public function __construct(InjectionChainValue $icv)
    {
        $this->icv = $icv;
    }
}

class InjectionChainTest
{
    public $icv;
    public $dependency;

    public function __construct(
        InjectionChainTestDependency $ictd,
        InjectionChainValue $icv
    ) {
        $this->dependency = $ictd;
        $this->icv        = $icv;
    }
}

interface Model
{
    public function userName(): Name;
}

interface Name
{
}

class Person implements Name
{
    public function __construct(protected ?string $userName = null)
    {
    }
}

class UserModel implements Model
{
    public function __construct(
        protected ?Name $userName = null
    ) {
    }

    public function userName(): Name
    {
        return $this->userName;
    }
}

class FakeServiceProvider extends BaseServiceProvider
{
    public function register(ServiceContainer $container): void
    {
        $container->alias('user.model', UserModel::class)
            ->define('user.model', [':userName' => new Person('Joseph Smith')]);
    }
}
