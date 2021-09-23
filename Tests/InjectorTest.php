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


namespace Qubus\Tests\Injector;

use Qubus\Exception\Exception;
use Qubus\Injector\InjectionException;
use Qubus\Injector\InjectionChain;
use Qubus\Injector\Injector;
use Qubus\Injector\InjectorException;
use stdClass;
use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Injector\Config\Factory;
use Qubus\Injector\ConfigException;
use TypeError;

class InjectorTest extends TestCase
{
    public function testMakeInstancesThroughConfigAlias()
    {
        $injector = new Injector(Factory::create([
            Injector::STANDARD_ALIASES => [
                'BNFoo' => 'Qubus\Tests\Injector\NotSharedClass',
            ],
            Injector::SHARED_ALIASES   => [
                'BNBar' => 'Qubus\Tests\Injector\SharedClass',
            ],
        ]));

        $objFooA  = $injector->make('BNFoo');
        $objFooB  = $injector->make('BNFoo');
        $objBarA  = $injector->make('BNBar');
        $objBarB  = $injector->make('BNBar');
        Assert::assertInstanceOf(
            'Qubus\Tests\Injector\NotSharedClass',
            $objFooA
        );
        Assert::assertInstanceOf(
            'Qubus\Tests\Injector\NotSharedClass',
            $objFooB
        );
        Assert::assertInstanceOf(
            'Qubus\Tests\Injector\SharedClass',
            $objBarA
        );
        Assert::assertInstanceOf(
            'Qubus\Tests\Injector\SharedClass',
            $objBarB
        );
        Assert::assertNotSame($objFooA, $objFooB);
        Assert::assertSame($objBarA, $objBarB);
    }

    public function testArgumentDefinitionsThroughConfig()
    {
        $injector = new Injector(Factory::create([
            Injector::ARGUMENT_DEFINITIONS => [
                'Qubus\Tests\Injector\DependencyWithDefinedParam' => [
                    'foo' => 42,
                ],
            ],
        ]));

        $obj = $injector->make('Qubus\Tests\Injector\DependencyWithDefinedParam');
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

        $obj      = $injector->make('stdClass');
        Assert::assertInstanceOf(SomeClassName::class, $obj);
    }

    public function testPreparationsThroughConfig()
    {
        $injector = new Injector(Factory::create([
            Injector::PREPARATIONS => [
                'stdClass' => function ($obj, $injector) {
                    $obj->testval = 42;
                },
                'Qubus\Tests\Injector\SomeInterface' => function ($obj, $injector) {
                    $obj->testProp = 42;
                },
            ],
        ]));

        $obj1     = $injector->make('stdClass');
        Assert::assertSame(42, $obj1->testval);
        $obj2 = $injector->make('Qubus\Tests\Injector\PreparesImplementationTest');
        Assert::assertSame(42, $obj2->testProp);
    }

    public function testMakeInstanceInjectsSimpleConcreteDependency()
    {
        $injector = new Injector(Factory::create([]));
        Assert::assertEquals(
            new TestNeedsDep(new TestDependency),
            $injector->make('Qubus\Tests\Injector\TestNeedsDep')
        );
    }

    public function testMakeInstanceReturnsNewInstanceIfClassHasNoConstructor()
    {
        $injector = new Injector(Factory::create([]));
        Assert::assertEquals(
            new TestNoConstructor,
            $injector->make('Qubus\Tests\Injector\TestNoConstructor')
        );
    }

    public function testMakeInstanceReturnsAliasInstanceOnNonConcreteTypehint()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\DepInterface',
            'Qubus\Tests\Injector\DepImplementation'
        );
        Assert::assertEquals(
            new DepImplementation,
            $injector->make('Qubus\Tests\Injector\DepInterface')
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
        $injector->make('Qubus\Tests\Injector\DepInterface');
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
        $injector->make('Qubus\Tests\Injector\RequiresInterface');
    }

    public function testMakeInstanceBuildsNonConcreteCtorParamWithAlias()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\DepInterface',
            'Qubus\Tests\Injector\DepImplementation'
        );
        $obj = $injector->make('Qubus\Tests\Injector\RequiresInterface');
        Assert::assertInstanceOf(
            'Qubus\Tests\Injector\RequiresInterface',
            $obj
        );
    }

    public function testMakeInstancePassesNullCtorParameterIfNoTypehintOrDefaultCanBeDetermined()
    {
        $injector         = new Injector(Factory::create([]));
        $nullCtorParamObj = $injector->make('Qubus\Tests\Injector\ProvTestNoDefinitionNullDefaultClass');
        Assert::assertEquals(new ProvTestNoDefinitionNullDefaultClass, $nullCtorParamObj);
        Assert::assertEquals(null, $nullCtorParamObj->arg);
    }

    public function testMakeInstanceReturnsSharedInstanceIfAvailable()
    {
        $injector = new Injector(Factory::create([]));
        $injector->define(
            'Qubus\Tests\Injector\RequiresInterface',
            ['dep' => 'Qubus\Tests\Injector\DepImplementation']
        );
        $injector->share('Qubus\Tests\Injector\RequiresInterface');
        $injected = $injector->make('Qubus\Tests\Injector\RequiresInterface');

        Assert::assertEquals('something', $injected->testDep->testProp);
        $injected->testDep->testProp = 'something else';

        $injected2 = $injector->make('Qubus\Tests\Injector\RequiresInterface');
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
            'Qubus\Tests\Injector\TestNeedsDep',
            ['testDep' => 'Qubus\Tests\Injector\TestDependency']
        );
        $injected = $injector->make(
            'Qubus\Tests\Injector\TestNeedsDep',
            ['testDep' => 'Qubus\Tests\Injector\TestDependency2']
        );
        Assert::assertEquals('testVal2', $injected->testDep->testProp);
    }

    public function testMakeInstanceCustomDefinitionOverridesExistingDefinitions()
    {
        $injector = new Injector(Factory::create([]));
        $injector->define(
            'Qubus\Tests\Injector\InjectorTestChildClass',
            [
                ':arg1' => 'First argument',
                ':arg2' => 'Second argument',
            ]
        );
        $injected = $injector->make(
            'Qubus\Tests\Injector\InjectorTestChildClass',
            [':arg1' => 'Override']
        );
        Assert::assertEquals('Override', $injected->arg1);
        Assert::assertEquals('Second argument', $injected->arg2);
    }

    public function testMakeInstanceStoresShareIfMarkedWithNullInstance()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share('Qubus\Tests\Injector\TestDependency');
        $obj = $injector->make('Qubus\Tests\Injector\TestDependency');
        Assert::assertInstanceOf('Qubus\Tests\Injector\TestDependency', $obj);
    }

    public function testMakeInstanceUsesReflectionForUnknownParamsInMultiBuildWithDeps()
    {
        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make(
            'Qubus\Tests\Injector\TestMultiDepsWithCtor',
            ['val1' => 'Qubus\Tests\Injector\TestDependency']
        );
        Assert::assertInstanceOf('Qubus\Tests\Injector\TestMultiDepsWithCtor', $obj);

        $obj = $injector->make(
            'Qubus\Tests\Injector\NoTypehintNoDefaultConstructorClass',
            ['val1' => 'Qubus\Tests\Injector\TestDependency']
        );
        Assert::assertInstanceOf('Qubus\Tests\Injector\NoTypehintNoDefaultConstructorClass', $obj);
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
        $obj      = $injector->make('Qubus\Tests\Injector\InjectorTestCtorParamWithNoTypehintOrDefault');
        Assert::assertNull($obj->val);
    }

    /**
     * @expectedException InjectionException
     * @expectedExceptionCode InjectorException::E_UNDEFINED_PARAM
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithoutDefinitionOrDefaultThroughAliasedTypehint(
    ) {
        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);

        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\TestNoExplicitDefine',
            'Qubus\Tests\Injector\InjectorTestCtorParamWithNoTypehintOrDefault'
        );
        $injector->make('Qubus\Tests\Injector\InjectorTestCtorParamWithNoTypehintOrDefaultDependent');
    }

    /**
     * @TODO
     * @expectedException InjectorException
     */
    public function testMakeInstanceThrowsExceptionOnUninstantiableTypehintWithoutDefinition()
    {
        $this->expectException(InjectionException::class);

        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make('Qubus\Tests\Injector\RequiresInterface');
    }

    public function testTypelessDefineForDependency()
    {
        $thumbnailSize = 128;
        $injector      = new Injector(Factory::create([]));
        $injector->defineParam('thumbnailSize', $thumbnailSize);
        $testClass = $injector->make('Qubus\Tests\Injector\RequiresDependencyWithTypelessParameters');
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
            'Qubus\Tests\Injector\TestNoExplicitDefine',
            'Qubus\Tests\Injector\ProviderTestCtorParamWithNoTypehintOrDefault'
        );
        $obj = $injector->make('Qubus\Tests\Injector\ProviderTestCtorParamWithNoTypehintOrDefaultDependent');
        Assert::assertInstanceOf(
            'Qubus\Tests\Injector\ProviderTestCtorParamWithNoTypehintOrDefaultDependent',
            $obj
        );
    }

    public function testMakeInstanceInjectsRawParametersDirectly()
    {
        $injector = new Injector(Factory::create([]));
        $injector->define(
            'Qubus\Tests\Injector\InjectorTestRawCtorParams',
            [
                ':string' => 'string',
                ':obj'    => new stdClass,
                ':int'    => 42,
                ':array'  => [],
                ':float'  => 9.3,
                ':bool'   => true,
                ':null'   => null,
            ]
        );

        $obj = $injector->make('Qubus\Tests\Injector\InjectorTestRawCtorParams');
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
        $obj      = $injector->make('Qubus\Tests\Injector\SomeClassName');
        Assert::assertInstanceOf('Qubus\Tests\Injector\SomeClassName', $obj);
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

        $injector->delegate('Qubus\Tests\Injector\TestDependency', $callable);

        $obj = $injector->make('Qubus\Tests\Injector\TestDependency');

        Assert::assertInstanceOf('Qubus\Tests\Injector\TestDependency', $obj);
    }

    public function testMakeInstanceWithStringDelegate()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            'stdClass',
            'Qubus\Tests\Injector\StringstdClassDelegateMock'
        );
        $obj = $injector->make('stdClass');
        Assert::assertEquals(42, $obj->test);
    }

    /**
     * @expectedException ConfigException
     */
    public function testMakeInstanceThrowsExceptionIfStringDelegateClassHasNoInvokeMethod()
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(Factory::create([]));
        $injector->delegate('stdClass', 'StringDelegateWithNoInvokeMethod');
    }

    /**
     * @expectedException ConfigException
     */
    public function testMakeInstanceThrowsExceptionIfStringDelegateClassInstantiationFails()
    {
        $this->expectException(ConfigException::class);

        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            'stdClass',
            'SomeClassThatDefinitelyDoesNotExistForReal'
        );
    }

    /**
     * @expectedException InjectionException
     */
    public function testMakeInstanceThrowsExceptionOnUntypehintedParameterWithNoDefinition()
    {
        $this->expectException(InjectionException::class);

        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make('Qubus\Tests\Injector\RequiresInterface');
    }

    public function testDefineAssignsPassedDefinition()
    {
        $injector   = new Injector(Factory::create([]));
        $definition = ['dep' => 'Qubus\Tests\Injector\DepImplementation'];
        $injector->define(
            'Qubus\Tests\Injector\RequiresInterface',
            $definition
        );
        Assert::assertInstanceOf(
            'Qubus\Tests\Injector\RequiresInterface',
            $injector->make('Qubus\Tests\Injector\RequiresInterface')
        );
    }

    public function testShareStoresSharedInstanceAndReturnsCurrentInstance()
    {
        $injector        = new Injector(Factory::create([]));
        $testShare       = new stdClass;
        $testShare->test = 42;

        Assert::assertInstanceOf(
            'Qubus\Injector\Injector',
            $injector->share($testShare)
        );
        $testShare->test = 'test';
        Assert::assertEquals('test', $injector->make('stdClass')->test);
    }

    public function testShareMarksClassSharedOnNullObjectParameter()
    {
        $injector = new Injector(Factory::create([]));
        Assert::assertInstanceOf(
            'Qubus\Injector\Injector',
            $injector->share('SomeClass')
        );
    }

    public function testAliasAssignsValueAndReturnsCurrentInstance()
    {
        $injector = new Injector(Factory::create([]));
        Assert::assertInstanceOf(
            'Qubus\Injector\Injector',
            $injector->alias(
                'DepInterface',
                'Qubus\Tests\Injector\DepImplementation'
            )
        );
    }

    public function provideInvalidDelegates()
    {
        return [
            [new stdClass],
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
        $injector->delegate('Qubus\Tests\Injector\TestDependency', $badDelegate);
    }

    public function testDelegateInstantiatesCallableClassString()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            'Qubus\Tests\Injector\MadeByDelegate',
            'Qubus\Tests\Injector\CallableDelegateClassTest'
        );
        Assert::assertInstanceof(
            'Qubus\Tests\Injector\MadeByDelegate',
            $injector->make('Qubus\Tests\Injector\MadeByDelegate')
        );
    }

    public function testDelegateInstantiatesCallableClassArray()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            'Qubus\Tests\Injector\MadeByDelegate',
            [
                'Qubus\Tests\Injector\CallableDelegateClassTest',
                '__invoke',
            ]
        );
        Assert::assertInstanceof(
            'Qubus\Tests\Injector\MadeByDelegate',
            $injector->make('Qubus\Tests\Injector\MadeByDelegate')
        );
    }

    public function testUnknownDelegationFunction()
    {
        $injector = new Injector(Factory::create([]));
        try {
            $injector->delegate('Qubus\Tests\Injector\DelegatableInterface', 'FunctionWhichDoesNotExist');
            $this->fail("Delegation was supposed to fail.");
        } catch (InjectorException $ie) {
            Assert::assertStringContainsString('FunctionWhichDoesNotExist', $ie->getMessage());
            Assert::assertEquals(
                InjectorException::E_DELEGATE_ARGUMENT,
                $ie->getCode()
            );
        }
    }

    public function testUnknownDelegationMethod()
    {
        $injector = new Injector(Factory::create([]));
        try {
            $injector->delegate(
                'Qubus\Tests\Injector\DelegatableInterface',
                ['stdClass', 'methodWhichDoesNotExist']
            );
            $this->fail("Delegation was supposed to fail.");
        } catch (InjectorException $ie) {
            Assert::assertStringContainsString('stdClass', $ie->getMessage());
            Assert::assertStringContainsString('methodWhichDoesNotExist', $ie->getMessage());
            Assert::assertEquals(InjectorException::E_DELEGATE_ARGUMENT, $ie->getCode());
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
            'Qubus\Tests\Injector\ExecuteClassNoDeps',
            'execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 1 -------------------------------------------------------------------------------------->

        $toInvoke       = [new ExecuteClassNoDeps, 'execute'];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 2 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            'Qubus\Tests\Injector\ExecuteClassDeps',
            'execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 3 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            new ExecuteClassDeps(new TestDependency),
            'execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 4 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            'Qubus\Tests\Injector\ExecuteClassDepsWithMethodDeps',
            'execute',
        ];
        $args           = [':arg' => 9382];
        $expectedResult = 9382;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 5 -------------------------------------------------------------------------------------->

        $toInvoke       = [
            'Qubus\Tests\Injector\ExecuteClassStaticMethod',
            'execute',
        ];
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 6 -------------------------------------------------------------------------------------->

        $toInvoke       = [new ExecuteClassStaticMethod, 'execute'];
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
            'Qubus\Tests\Injector\ExecuteClassRelativeStaticMethod',
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

        $toInvoke       = new ExecuteClassInvokable;
        $args           = [];
        $expectedResult = 42;
        $return[]       = [$toInvoke, $args, $expectedResult];

        // 12 ------------------------------------------------------------------------------------->

        $toInvoke       = 'Qubus\Tests\Injector\ExecuteClassInvokable';
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
            'Qubus\Tests\Injector\DelegatableInterface',
            'Qubus\Tests\Injector\ImplementsInterfaceFactory'
        );
        $requiresDelegatedInterface = $injector->make('Qubus\Tests\Injector\RequiresDelegatedInterface');
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
        $testClass = $injector->make('Qubus\Tests\Injector\TestMissingDependency');
    }

    public function testAliasingConcreteClasses()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias('Qubus\Tests\Injector\ConcreteClass1', 'Qubus\Tests\Injector\ConcreteClass2');
        $obj = $injector->make('Qubus\Tests\Injector\ConcreteClass1');
        Assert::assertInstanceOf('Qubus\Tests\Injector\ConcreteClass2', $obj);
    }

    public function testSharedByAliasedInterfaceName()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\SharedAliasedInterface',
            'Qubus\Tests\Injector\SharedClass'
        );
        $injector->share('Qubus\Tests\Injector\SharedAliasedInterface');
        $class  = $injector->make('Qubus\Tests\Injector\SharedAliasedInterface');
        $class2 = $injector->make('Qubus\Tests\Injector\SharedAliasedInterface');
        Assert::assertSame($class, $class2);
    }

    public function testNotSharedByAliasedInterfaceName()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\SharedAliasedInterface',
            'Qubus\Tests\Injector\SharedClass'
        );
        $injector->alias(
            'Qubus\Tests\Injector\SharedAliasedInterface',
            'Qubus\Tests\Injector\NotSharedClass'
        );
        $injector->share('Qubus\Tests\Injector\SharedClass');
        $class  = $injector->make('Qubus\Tests\Injector\SharedAliasedInterface');
        $class2 = $injector->make('Qubus\Tests\Injector\SharedAliasedInterface');

        Assert::assertNotSame($class, $class2);
    }

    public function testSharedByAliasedInterfaceNameReversedOrder()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share('Qubus\Tests\Injector\SharedAliasedInterface');
        $injector->alias(
            'Qubus\Tests\Injector\SharedAliasedInterface',
            'Qubus\Tests\Injector\SharedClass'
        );
        $class  = $injector->make('Qubus\Tests\Injector\SharedAliasedInterface');
        $class2 = $injector->make('Qubus\Tests\Injector\SharedAliasedInterface');
        Assert::assertSame($class, $class2);
    }

    public function testSharedByAliasedInterfaceNameWithParameter()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\SharedAliasedInterface',
            'Qubus\Tests\Injector\SharedClass'
        );
        $injector->share('Qubus\Tests\Injector\SharedAliasedInterface');
        $sharedClass = $injector->make('Qubus\Tests\Injector\SharedAliasedInterface');
        $childClass  = $injector->make('Qubus\Tests\Injector\ClassWithAliasAsParameter');
        Assert::assertSame($sharedClass, $childClass->sharedClass);
    }

    public function testSharedByAliasedInstance()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\SharedAliasedInterface',
            'Qubus\Tests\Injector\SharedClass'
        );
        $sharedClass = $injector->make('Qubus\Tests\Injector\SharedAliasedInterface');
        $injector->share($sharedClass);
        $childClass = $injector->make('Qubus\Tests\Injector\ClassWithAliasAsParameter');
        Assert::assertSame($sharedClass, $childClass->sharedClass);
    }

    public function testMultipleShareCallsDontOverrideTheOriginalSharedInstance()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share('stdClass');
        $stdClass1 = $injector->make('stdClass');
        $injector->share('stdClass');
        $stdClass2 = $injector->make('stdClass');
        Assert::assertSame($stdClass1, $stdClass2);
    }

    public function testDependencyWhereSharedWithProtectedConstructor()
    {
        $injector = new Injector(Factory::create([]));

        $inner = TestDependencyWithProtectedConstructor::create();
        $injector->share($inner);

        $outer = $injector->make('Qubus\Tests\Injector\TestNeedsDepWithProtCons');

        Assert::assertSame($inner, $outer->dep);
    }

    public function testDependencyWhereShared()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share('Qubus\Tests\Injector\ClassInnerB');
        $innerDep = $injector->make('Qubus\Tests\Injector\ClassInnerB');
        $inner    = $injector->make('Qubus\Tests\Injector\ClassInnerA');
        Assert::assertSame($innerDep, $inner->dep);
        $outer = $injector->make('Qubus\Tests\Injector\ClassOuter');
        Assert::assertSame($innerDep, $outer->dep->dep);
    }

    public function testBugWithReflectionPoolIncorrectlyReturningBadInfo()
    {
        $injector = new Injector(Factory::create([]));
        $obj      = $injector->make('Qubus\Tests\Injector\ClassOuter');
        Assert::assertInstanceOf('Qubus\Tests\Injector\ClassOuter', $obj);
        Assert::assertInstanceOf('Qubus\Tests\Injector\ClassInnerA', $obj->dep);
        Assert::assertInstanceOf('Qubus\Tests\Injector\ClassInnerB', $obj->dep->dep);
    }

    public function provideCyclicDependencies()
    {
        return [
            'Qubus\Tests\Injector\RecursiveClassA' => ['Qubus\Tests\Injector\RecursiveClassA'],
            'Qubus\Tests\Injector\RecursiveClassB' => ['Qubus\Tests\Injector\RecursiveClassB'],
            'Qubus\Tests\Injector\RecursiveClassC' => ['Qubus\Tests\Injector\RecursiveClassC'],
            'Qubus\Tests\Injector\RecursiveClass1' => ['Qubus\Tests\Injector\RecursiveClass1'],
            'Qubus\Tests\Injector\RecursiveClass2' => ['Qubus\Tests\Injector\RecursiveClass2'],
            'Qubus\Tests\Injector\DependsOnCyclic' => ['Qubus\Tests\Injector\DependsOnCyclic'],
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
        $class    = $injector->make('Qubus\Tests\Injector\NonConcreteDependencyWithDefaultValue');
        Assert::assertInstanceOf('Qubus\Tests\Injector\NonConcreteDependencyWithDefaultValue', $class);
        Assert::assertNull($class->interface);
    }

    public function testNonConcreteDependencyWithDefaultValueThroughAlias()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\DelegatableInterface',
            'Qubus\Tests\Injector\ImplementsInterface'
        );
        $class = $injector->make('Qubus\Tests\Injector\NonConcreteDependencyWithDefaultValue');
        Assert::assertInstanceOf('Qubus\Tests\Injector\NonConcreteDependencyWithDefaultValue', $class);
        Assert::assertNotInstanceOf('Qubus\Tests\Injector\ImplementsInterface', $class->interface);
    }

    public function testNonConcreteDependencyWithDefaultValueThroughDelegation()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            'Qubus\Tests\Injector\DelegatableInterface',
            'Qubus\Tests\Injector\ImplementsInterfaceFactory'
        );
        $class = $injector->make('Qubus\Tests\Injector\NonConcreteDependencyWithDefaultValue');
        Assert::assertInstanceOf('Qubus\Tests\Injector\NonConcreteDependencyWithDefaultValue', $class);
        Assert::assertNotInstanceOf('Qubus\Tests\Injector\ImplementsInterface', $class->interface);
    }

    public function testDependencyWithDefaultValueThroughShare()
    {
        $injector = new Injector(Factory::create([]));
        //Instance is not shared, null default is used for dependency
        $instance = $injector->make('Qubus\Tests\Injector\ConcreteDependencyWithDefaultValue');
        Assert::assertNull($instance->dependency);

        //Instance is explicitly shared, $instance is used for dependency
        $instance = new stdClass();
        $injector->share($instance);
        $instance = $injector->make('Qubus\Tests\Injector\ConcreteDependencyWithDefaultValue');
        Assert::assertNotInstanceOf('stdClass', $instance->dependency);
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
        $injector->alias('stdClass', 'Qubus\Tests\Injector\SomeOtherClass');
        $injector->share($testClass);
    }

    public function testShareAfterAliasAliasedClassAllowed()
    {
        $injector  = new Injector(Factory::create([]));
        $testClass = new DepImplementation();
        $injector->alias('Qubus\Tests\Injector\DepInterface', 'Qubus\Tests\Injector\DepImplementation');
        $injector->share($testClass);
        $obj = $injector->make('Qubus\Tests\Injector\DepInterface');
        Assert::assertInstanceOf('Qubus\Tests\Injector\DepImplementation', $obj);
    }

    public function testAliasAfterShareByStringAllowed()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share('Qubus\Tests\Injector\DepInterface');
        $injector->alias('Qubus\Tests\Injector\DepInterface', 'Qubus\Tests\Injector\DepImplementation');
        $obj  = $injector->make('Qubus\Tests\Injector\DepInterface');
        $obj2 = $injector->make('Qubus\Tests\Injector\DepInterface');
        Assert::assertInstanceOf('Qubus\Tests\Injector\DepImplementation', $obj);
        Assert::assertEquals($obj, $obj2);
    }

    public function testAliasAfterShareBySharingAliasAllowed()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share('Qubus\Tests\Injector\DepImplementation');
        $injector->alias('Qubus\Tests\Injector\DepInterface', 'Qubus\Tests\Injector\DepImplementation');
        $obj  = $injector->make('Qubus\Tests\Injector\DepInterface');
        $obj2 = $injector->make('Qubus\Tests\Injector\DepInterface');
        Assert::assertInstanceOf('Qubus\Tests\Injector\DepImplementation', $obj);
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
        $injector->alias('stdClass', 'Qubus\Tests\Injector\SomeOtherClass');
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
        $injector->make('Qubus\Tests\Injector\HasNonPublicConstructor');
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
        $injector->make('Qubus\Tests\Injector\HasNonPublicConstructorWithArgs');
    }

    public function testMakeExecutableFailsOnNonExistentFunction()
    {
        $injector = new Injector(Factory::create([]));
        $this->expectException(
            'Qubus\Injector\InjectionException',
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
            'Qubus\Injector\InjectionException',
            "[object(stdClass), 'nonExistentMethod']",
            InjectorException::E_INVOKABLE
        );
        $injector->buildExecutable([$object, 'nonExistentMethod']);
    }

    public function testMakeExecutableFailsOnNonExistentStaticMethod()
    {
        $injector = new Injector(Factory::create([]));
        $this->expectException(
            'Qubus\Injector\InjectionException',
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
        $injector->share('Qubus\Tests\Injector\DepInterface');
        $injector->alias('Qubus\Tests\Injector\DepInterface', '');
    }

    public function testShareNewAlias()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share('Qubus\Tests\Injector\DepImplementation');
        $injector->alias('Qubus\Tests\Injector\DepInterface', 'Qubus\Tests\Injector\DepImplementation');
        Assert::assertTrue(true);
    }

    public function testDefineWithBackslashAndMakeWithoutBackslash()
    {
        $injector = new Injector(Factory::create([]));
        $injector->define('Qubus\Tests\Injector\SimpleNoTypehintClass', [':arg' => 'tested']);
        $testClass = $injector->make('Qubus\Tests\Injector\SimpleNoTypehintClass');
        Assert::assertEquals('tested', $testClass->testParam);
    }

    public function testShareWithBackslashAndMakeWithoutBackslash()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share('stdClass');
        $classA         = $injector->make('stdClass');
        $classA->tested = false;
        $classB         = $injector->make('stdClass');
        $classB->tested = true;

        Assert::assertEquals($classA->tested, $classB->tested);
    }

    public function testInstanceMutate()
    {
        $injector = new Injector(Factory::create([]));
        $injector->prepare('stdClass', function ($obj, $injector) {
            $obj->testval = 42;
        });
        $obj = $injector->make('stdClass');

        Assert::assertSame(42, $obj->testval);
    }

    public function testInterfaceMutate()
    {
        $injector = new Injector(Factory::create([]));
        $injector->prepare(
            'Qubus\Tests\Injector\SomeInterface',
            function ($obj, $injector) {
                $obj->testProp = 42;
            }
        );
        $obj = $injector->make('Qubus\Tests\Injector\PreparesImplementationTest');

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
        $injector->share('Qubus\Tests\Injector\DependencyWithDefinedParam');
        $injector->make('Qubus\Tests\Injector\RequiresDependencyWithDefinedParam', [':foo' => 5]);
    }

    public function testDelegationFunction()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            'Qubus\Tests\Injector\TestDelegationSimple',
            'Qubus\Tests\Injector\createTestDelegationSimple'
        );
        $obj = $injector->make('Qubus\Tests\Injector\TestDelegationSimple');
        Assert::assertInstanceOf('Qubus\Tests\Injector\TestDelegationSimple', $obj);
        Assert::assertTrue($obj->delegateCalled);
    }

    public function testDelegationDependency()
    {
        $injector = new Injector(Factory::create([]));
        $injector->delegate(
            'Qubus\Tests\Injector\TestDelegationDependency',
            'Qubus\Tests\Injector\createTestDelegationDependency'
        );
        $obj = $injector->make('Qubus\Tests\Injector\TestDelegationDependency');
        Assert::assertInstanceOf('Qubus\Tests\Injector\TestDelegationDependency', $obj);
        Assert::assertTrue($obj->delegateCalled);
    }

    public function testExecutableAliasing()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\BaseExecutableClass',
            'Qubus\Tests\Injector\ExtendsExecutableClass'
        );
        $result = $injector->execute([
            'Qubus\Tests\Injector\BaseExecutableClass',
            'foo',
        ]);
        Assert::assertEquals('This is the ExtendsExecutableClass', $result);
    }

    public function testExecutableAliasingStatic()
    {
        $injector = new Injector(Factory::create([]));
        $injector->alias(
            'Qubus\Tests\Injector\BaseExecutableClass',
            'Qubus\Tests\Injector\ExtendsExecutableClass'
        );
        $result = $injector->execute([
            'Qubus\Tests\Injector\BaseExecutableClass',
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
        $injector->delegate('Qubus\Tests\Injector\DelegateClosureInGlobalScope', $delegateClosure);
        $obj = $injector->make('Qubus\Tests\Injector\DelegateClosureInGlobalScope');
        Assert::assertInstanceOf('Qubus\Tests\Injector\DelegateClosureInGlobalScope', $obj);
    }

    public function testCloningWithServiceLocator()
    {
        $injector = new Injector(Factory::create([]));
        $injector->share($injector);
        $instance    = $injector->make('Qubus\Tests\Injector\CloneTest');
        $newInjector = $instance->injector;
        $newInstance = $newInjector->make('Qubus\Tests\Injector\CloneTest');
        Assert::assertInstanceOf('Qubus\Tests\Injector\CloneTest', $instance);
        Assert::assertInstanceOf('Qubus\Tests\Injector\CloneTest', $newInstance);
    }

    public function testAbstractExecute()
    {
        $injector = new Injector(Factory::create([]));

        $fn = fn () => new ConcreteExecuteTest();

        $injector->delegate('Qubus\Tests\Injector\AbstractExecuteTest', $fn);
        $result = $injector->execute([
            'Qubus\Tests\Injector\AbstractExecuteTest',
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
        $injector->delegate('Qubus\Tests\Injector\SomeClassName', $delegate);
        $injector->make('Qubus\Tests\Injector\SomeClassName');
    }

    public function testPrepareCallableReplacesObjectWithReturnValueOfSameInterfaceType()
    {
        $injector = new Injector(Factory::create([]));
        $expected = new SomeImplementation; // <-- implements SomeInterface
        $injector->prepare(
            'Qubus\Tests\Injector\SomeInterface',
            function ($impl) use ($expected) {
                return $expected;
            }
        );
        $actual = $injector->make('Qubus\Tests\Injector\SomeImplementation');
        Assert::assertSame($expected, $actual);
    }

    public function testPrepareCallableReplacesObjectWithReturnValueOfSameClassType()
    {
        $injector = new Injector(Factory::create([]));
        $expected = new SomeImplementation; // <-- implements SomeInterface
        $injector->prepare(
            'Qubus\Tests\Injector\SomeImplementation',
            function ($impl) use ($expected) {
                return $expected;
            }
        );
        $actual = $injector->make('Qubus\Tests\Injector\SomeImplementation');
        Assert::assertSame($expected, $actual);
    }

    public function testChildWithoutConstructorWorks()
    {
        $injector = new Injector(Factory::create([]));
        try {
            $injector->define('Qubus\Tests\Injector\ParentWithConstructor', [':foo' => 'parent']);
            $injector->define('Qubus\Tests\Injector\ChildWithoutConstructor', [':foo' => 'child']);

            $injector->share('Qubus\Tests\Injector\ParentWithConstructor');
            $injector->share('Qubus\Tests\Injector\ChildWithoutConstructor');

            $child = $injector->make('Qubus\Tests\Injector\ChildWithoutConstructor');
            Assert::assertEquals('child', $child->foo);

            $parent = $injector->make('Qubus\Tests\Injector\ParentWithConstructor');
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
        $injector->define('Qubus\Tests\Injector\ParentWithConstructor', [':foo' => 'parent']);
        $injector->make('Qubus\Tests\Injector\ChildWithoutConstructor');
    }

    public function testInjectionChainValue()
    {
        $fn = function (InjectionChain $ic) {
            if ($ic->getByIndex(-2) ===
                'Qubus\Tests\Injector\InjectionChainTestDependency'
            ) {
                return new InjectionChainValue('Value for dependency');
            } elseif ($ic->getByIndex(-2) ===
                       'Qubus\Tests\Injector\InjectionChainTest'
            ) {
                return new InjectionChainValue('Value for parent');
            }

            return new InjectionChainValue('unknown value');
        };

        $injector = new Injector(Factory::create([]));
        $injector->share($injector);
        $injector->delegate('Qubus\Tests\Injector\InjectionChainValue', $fn);
        $injector->delegate('Qubus\Injector\InjectionChain', [$injector, 'getInjectionChain']);

        $object = $injector->make('Qubus\Tests\Injector\InjectionChainTest');
        Assert::assertEquals($object->icv->value, 'unknown value');
        Assert::assertEquals($object->dependency->icv->value, 'unknown value');
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

    public function __construct(ConfigInterface $config)
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
        $obj       = new \StdClass;
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
        return isset($arg) ? $arg : 42;
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
        return new MadeByDelegate;
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

    public function __construct(DelegatableInterface $i = null)
    {
        $this->interface = $i;
    }
}

class ConcreteDependencyWithDefaultValue
{
    public $dependency;

    public function __construct(\StdClass $instance = null)
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
    $instance                 = new TestDelegationSimple;
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
        $callable = function () {
            return $this->value;
        };

        return $callable;
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
