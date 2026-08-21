<?php

declare(strict_types=1);

namespace Qubus\Injector\Test;

use PHPUnit\Framework\TestCase;
use Qubus\Injector\Cache\CachingReflector;
use Qubus\Injector\Injector;
use Qubus\Injector\Test\Fixture\LegacyCachingReflector;
use Qubus\Injector\Test\Fixture\LegacyConfig;
use Qubus\Injector\Test\Fixture\LegacyContainer;
use Qubus\Injector\Test\Fixture\LegacyExecutable;
use Qubus\Injector\Test\Fixture\LegacyInjectionException;
use Qubus\Injector\Test\Fixture\LegacyInjector;
use Qubus\Injector\Test\Fixture\LegacyInjectorConfig;
use Qubus\Injector\Test\Fixture\LegacyReflectionCache;
use ReflectionFunction;

final class BackwardCompatibilityTest extends TestCase
{
    public function testLegacyConfigImplementationRemainsUsable(): void
    {
        $injector = new Injector(new LegacyConfig());
        $config = new LegacyInjectorConfig(['value' => 'configured']);

        self::assertInstanceOf(Injector::class, $injector);
        self::assertSame('configured', $config->get('value'));
    }

    public function testLegacyReflectionCacheImplementationRemainsUsable(): void
    {
        $reflector = new CachingReflector(cache: new LegacyReflectionCache());

        self::assertSame(self::class, $reflector->getClass(self::class)->getName());
    }

    public function testPreviouslyExtensiblePublicClassesRemainExtensible(): void
    {
        $executable = new LegacyExecutable(new ReflectionFunction(static fn (): bool => true));
        $exception = new LegacyInjectionException([]);
        $injector = new LegacyInjector(new LegacyConfig());
        $reflector = new LegacyCachingReflector();

        self::assertNull($executable->getInvocationObject());
        self::assertSame([], $exception->getDependencyChain());
        self::assertSame(Injector::I_ALL, $injector::I_ALL);
        self::assertNull($injector->legacyConfig());
        self::assertInstanceOf(CachingReflector::class, $injector->legacyReflector());
        self::assertSame(
            CachingReflector::CACHE_KEY_CLASSES,
            $reflector::CACHE_KEY_CLASSES
        );
    }

    public function testPsrContainerLookupCacheRemainsAvailableToSubclasses(): void
    {
        $container = new LegacyContainer(new LegacyConfig());

        self::assertFalse($container->has('missing.entry'));
        self::assertSame(['missing.entry' => false], $container->lookupCache());
    }
}
