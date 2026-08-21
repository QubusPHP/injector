<?php

declare(strict_types=1);

namespace Qubus\Injector\Test;

use PHPUnit\Framework\TestCase;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\ConfigException;
use Qubus\Injector\InjectionException;
use Qubus\Injector\Injector;
use Qubus\Injector\InjectorException;
use Qubus\Injector\InvalidMappingsException;
use Qubus\Injector\Test\Fixture\RequiredScalarDependency;
use Qubus\Injector\Test\Fixture\InaccessibleExecutableClassMethod;
use Qubus\Injector\Test\Fixture\InaccessibleStaticExecutableClassMethod;
use Qubus\Injector\Test\Fixture\ClassWithStaticMethodThatTakesArg;
use Qubus\Injector\Test\Fixture\InjectionChainFixture;
use Qubus\Injector\Test\Fixture\InjectionChainTestDependency;
use Qubus\Injector\Test\Fixture\InjectionChainValue;
use Qubus\Injector\Test\Fixture\SomeClassName;
use stdClass;

class InjectorRegressionTest extends TestCase
{
    public function testAliasChainsResolveToTheirFinalTarget(): void
    {
        $injector = new Injector(InjectorFactory::create());
        $injector->alias('first.alias', 'second.alias');
        $injector->alias('second.alias', SomeClassName::class);

        self::assertInstanceOf(SomeClassName::class, $injector->make('first.alias'));
    }

    public function testAliasCyclesAreRejected(): void
    {
        $injector = new Injector(InjectorFactory::create());
        $injector->alias('first.alias', 'second.alias');

        $this->expectException(ConfigException::class);
        $this->expectExceptionCode(InjectorException::E_CYCLIC_ALIAS);
        $injector->alias('second.alias', 'first.alias');
    }

    public function testDelegateRegisteredThroughAliasUsesResolvedTarget(): void
    {
        $expected = new SomeClassName();
        $injector = new Injector(InjectorFactory::create());
        $injector->alias('service.alias', SomeClassName::class);
        $injector->delegate('service.alias', static fn (): SomeClassName => $expected);

        self::assertSame($expected, $injector->make('service.alias'));
    }

    public function testInvalidPreparerProducesInjectionException(): void
    {
        $injector = new Injector(InjectorFactory::create());

        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_INVOKABLE);
        $injector->prepare(SomeClassName::class, new stdClass());
    }

    public function testMalformedCallableArrayCanBeReportedSafely(): void
    {
        $exception = InjectionException::fromInvalidCallable([], [SomeClassName::class]);

        self::assertSame(InjectorException::E_INVOKABLE, $exception->getCode());
        self::assertSame(InjectorException::M_INVOKABLE, $exception->getMessage());
    }

    public function testRequiredBuiltinParameterUsesUndefinedParameterError(): void
    {
        $injector = new Injector(InjectorFactory::create());

        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_UNDEFINED_PARAM);
        $injector->make(RequiredScalarDependency::class);
    }

    public function testInvalidMappingShapeIsWrappedConsistently(): void
    {
        $this->expectException(InvalidMappingsException::class);

        new Injector(InjectorFactory::create([
            Injector::STANDARD_ALIASES => 'invalid',
        ]));
    }

    public function testMalformedNestedClassDefinitionHasDedicatedError(): void
    {
        $injector = new Injector(InjectorFactory::create());
        $injector->define(RequiredScalarDependency::class, ['@value' => 'invalid']);

        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_INVALID_DEFINITION);
        $injector->make(RequiredScalarDependency::class);
    }

    public function testPrivateInstanceMethodsCannotBeExecuted(): void
    {
        $injector = new Injector(InjectorFactory::create());

        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_INVOKABLE);
        $injector->execute([new InaccessibleExecutableClassMethod(), 'doSomethingPrivate']);
    }

    public function testProtectedStaticMethodsCannotBeExecuted(): void
    {
        $injector = new Injector(InjectorFactory::create());

        $this->expectException(InjectionException::class);
        $this->expectExceptionCode(InjectorException::E_INVOKABLE);
        $injector->execute([InaccessibleStaticExecutableClassMethod::class, 'doSomethingProtected']);
    }

    public function testStaticExecutableIsNotReportedAsInstanceMethod(): void
    {
        $injector = new Injector(InjectorFactory::create());
        $executable = $injector->buildExecutable([ClassWithStaticMethodThatTakesArg::class, 'doSomething']);

        self::assertFalse($executable->isInstanceMethod());
        self::assertNull($executable->getInvocationObject());
    }

    public function testDelegatesCanInspectTheCurrentInjectionChain(): void
    {
        $injector = new Injector(InjectorFactory::create());
        $injector->delegate(
            InjectionChainValue::class,
            static fn (): InjectionChainValue => new InjectionChainValue($injector->getInjectionChain()->getChain())
        );

        $fixture = $injector->make(InjectionChainFixture::class);

        self::assertSame(
            [strtolower(InjectionChainFixture::class), strtolower(InjectionChainTestDependency::class)],
            $fixture->dependency->icv->value
        );
        self::assertSame([strtolower(InjectionChainFixture::class)], $fixture->icv->value);
    }
}
