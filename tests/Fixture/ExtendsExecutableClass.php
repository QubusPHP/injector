<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ExtendsExecutableClass extends BaseExecutableClass
{
    public static function bar(): string
    {
        return 'This is the ExtendsExecutableClass';
    }

    public function foo(): string
    {
        return 'This is the ExtendsExecutableClass';
    }
}
