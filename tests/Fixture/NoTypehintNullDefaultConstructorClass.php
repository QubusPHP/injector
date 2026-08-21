<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class NoTypehintNullDefaultConstructorClass
{
    public $testParam = 1;

    public function __construct(TestDependency $val1, $arg = 42)
    {
        $this->testParam = $arg;
    }
}
