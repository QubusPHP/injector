<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class BaseExecutableClass
{
    public static function bar(): string
    {
        return 'This is the BaseExecutableClass';
    }

    public function foo(): string
    {
        return 'This is the BaseExecutableClass';
    }
}
