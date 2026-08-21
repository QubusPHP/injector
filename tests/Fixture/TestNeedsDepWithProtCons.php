<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class TestNeedsDepWithProtCons extends stdClass
{
    public function __construct(TestDependencyWithProtectedConstructor $dep)
    {
        $this->dep = $dep;
    }
}
