<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class TestDependencyWithProtectedConstructor
{
    protected function __construct()
    {
    }

    public static function create()
    {
        return new self();
    }
}
