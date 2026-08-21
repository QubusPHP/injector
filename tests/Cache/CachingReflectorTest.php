<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Cache;

use Countable;
use PHPUnit\Framework\TestCase;
use Qubus\Injector\Cache\ArrayReflectionCache;
use Qubus\Injector\Cache\CachingReflector;
use stdClass;
use Stringable;

class CachingReflectorTest extends TestCase
{
    public function testAcceptsObjectClassReferences(): void
    {
        $object = new stdClass();
        $reflector = new CachingReflector();

        self::assertSame(stdClass::class, $reflector->getClass($object)->getName());
        self::assertNull($reflector->getConstructor($object));
        self::assertNull($reflector->getConstructorParams($object));
    }

    public function testReflectsClosuresWithoutBuildingAnInvalidCacheKey(): void
    {
        $closure = static fn (): int => 42;

        self::assertSame($closure, new CachingReflector()->getFunction($closure)->getClosure());
    }

    public function testIgnoresCorruptCachedReflectionValues(): void
    {
        $cache = new ArrayReflectionCache();
        $cache->store(CachingReflector::CACHE_KEY_CLASSES . 'stdclass', 'invalid');

        self::assertSame(stdClass::class, new CachingReflector(cache: $cache)->getClass(stdClass::class)->getName());
    }

    public function testDnfTypesAreNotTreatedAsNamedClassHints(): void
    {
        $closure = static function ((Countable&Stringable)|null $dependency): void {
        };
        $reflector = new CachingReflector();
        $function = $reflector->getFunction($closure);

        self::assertNull($reflector->getParamTypeHint($function, $function->getParameters()[0]));
    }
}
