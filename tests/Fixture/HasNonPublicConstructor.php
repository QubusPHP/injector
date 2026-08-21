<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class HasNonPublicConstructor
{
    protected function __construct()
    {
    }
}
