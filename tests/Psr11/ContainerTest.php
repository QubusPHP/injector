<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Psr11;

use PHPUnit\Framework\TestCase;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Http\Client\NotFoundException as LegacyNotFoundException;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Psr11\Container;
use Qubus\Injector\Test\Fixture\SomeClassName;

class ContainerTest extends TestCase
{
    public function testMissingEntryUsesPsrNotFoundContract(): void
    {
        $container = new Container(InjectorFactory::create());

        try {
            $container->get('missing.entry');
            self::fail('A missing PSR-11 entry must throw.');
        } catch (NotFoundExceptionInterface $exception) {
            self::assertInstanceOf(LegacyNotFoundException::class, $exception);
            self::assertSame(404, $exception->getCode());
        }
    }

    public function testHasIsNotStaleAfterRegistration(): void
    {
        $container = new Container(InjectorFactory::create());
        self::assertFalse($container->has('runtime.entry'));

        $container->delegate('runtime.entry', static fn (): SomeClassName => new SomeClassName());

        self::assertTrue($container->has('runtime.entry'));
        self::assertInstanceOf(SomeClassName::class, $container->get('runtime.entry'));
    }

    public function testCreationErrorsUsePsrContainerExceptionContract(): void
    {
        $container = new Container(InjectorFactory::create());
        $container->delegate('invalid.entry', static fn (): null => null);

        $this->expectException(ContainerExceptionInterface::class);
        $container->get('invalid.entry');
    }
}
