<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class TestDelegationDependency extends stdClass
{
    public $delgateCalled = false;

    public function __construct(TestDelegationSimple $testDelegationSimple)
    {
    }
}
