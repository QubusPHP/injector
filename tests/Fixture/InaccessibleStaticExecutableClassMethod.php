<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class InaccessibleStaticExecutableClassMethod
{
    protected static function doSomethingProtected()
    {
        return 42;
    }

    private static function doSomethingPrivate()
    {
        return 42;
    }
}
