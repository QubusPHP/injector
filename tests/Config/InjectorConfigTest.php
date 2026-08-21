<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Config;

use JsonException;
use PHPUnit\Framework\TestCase;
use Qubus\Injector\Config\InjectorFactory;
use stdClass;

class InjectorConfigTest extends TestCase
{
    public function testSupportsMixedAndNullValues(): void
    {
        $object = new stdClass();
        $config = InjectorFactory::create([
            'null' => null,
            'object' => $object,
        ]);

        self::assertTrue($config->has('null'));
        self::assertNull($config['null']);
        self::assertSame($object, $config['object']);
    }

    public function testNestedLookupStopsSafelyAtScalarValue(): void
    {
        $config = InjectorFactory::create(['scalar' => 'value']);

        self::assertFalse($config->has('scalar.child'));
        self::assertSame('fallback', $config->get('scalar.child', 'fallback'));
    }

    public function testClonePreservesConfigurationWithoutSharingMutations(): void
    {
        $config = InjectorFactory::create(['original' => 'value']);
        $clone = clone $config;
        $clone->add('clone', true);

        self::assertSame('value', $clone->get('original'));
        self::assertFalse($config->has('clone'));
    }

    public function testNullOffsetAppendsAValue(): void
    {
        $config = InjectorFactory::create();
        $config[] = 'value';

        self::assertSame('value', $config[0]);
    }

    public function testJsonEncodingDoesNotSilentlyDiscardErrors(): void
    {
        $config = InjectorFactory::create(['invalidUtf8' => "\xB1\x31"]);

        $this->expectException(JsonException::class);
        $config->toJson();
    }
}
