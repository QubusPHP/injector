<?php

declare(strict_types=1);

namespace Qubus\Injector\Test;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Injector\Config\InjectorFactory;
use Qubus\Injector\Injector;
use Qubus\Injector\Test\ServiceProvider\FakeServiceProvider;
use Qubus\Injector\Test\ServiceProvider\Person;

class ServiceProviderTest extends TestCase
{
    public function testFakeServiceProvider(): void
    {
        $injector = new Injector(InjectorFactory::create([]));

        $service = new FakeServiceProvider($injector);
        $service->register();

        $name = new Person('Joseph Smith');

        $injected = $injector->make('user.model');

        Assert::assertEquals($name, $injected->userName());
        Assert::assertInstanceOf(Person::class, $injected->userName());
    }
}
