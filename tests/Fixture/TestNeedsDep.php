<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class TestNeedsDep extends stdClass
{
    public function __construct(TestDependency $testDep)
    {
        $this->testDep = $testDep;
    }
}
