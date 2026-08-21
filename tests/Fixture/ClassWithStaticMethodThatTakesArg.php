<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ClassWithStaticMethodThatTakesArg
{
    public static function doSomething($arg)
    {
        return 1 + $arg;
    }
}
