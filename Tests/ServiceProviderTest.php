<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2021 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

namespace Qubus\Tests\Injector;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use Qubus\Injector\Config\Factory;
use Qubus\Injector\Psr11\Container;
use Qubus\Injector\Tests\ServiceProvider\FakeServiceProvider;
use Qubus\Injector\Tests\ServiceProvider\Person;

class ServiceProviderTest extends TestCase
{
    public function testFakeServiceProvider()
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
