<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class TestClassWithNoCtorTypehints
{
    public function __construct($val = 42)
    {
        $this->test = $val;
    }
}
