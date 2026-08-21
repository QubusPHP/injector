<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class InaccessibleExecutableClassMethod
{
    protected function doSomethingProtected()
    {
        return 42;
    }

    private function doSomethingPrivate()
    {
        return 42;
    }
}
