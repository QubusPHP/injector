<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class TestMultiDepsWithCtor extends stdClass
{
    public function __construct(TestDependency $val1, TestNeedsDep $val2)
    {
        $this->testDep = $val1;
        $this->testDep = $val2;
    }
}
