<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class HasNonPublicConstructorWithArgs
{
    protected function __construct($arg1, $arg2, $arg3)
    {
    }
}
