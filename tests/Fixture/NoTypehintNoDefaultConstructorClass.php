<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class NoTypehintNoDefaultConstructorClass
{
    public $testParam = 1;

    public function __construct(TestDependency $val1, $arg = null)
    {
        $this->testParam = $arg;
    }
}
