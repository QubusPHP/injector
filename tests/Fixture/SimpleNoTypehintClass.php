<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class SimpleNoTypehintClass
{
    public $testParam = 1;

    public function __construct($arg)
    {
        $this->testParam = $arg;
    }
}
