<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ExecuteClassDeps
{
    public function __construct(TestDependency $testDep)
    {
    }

    public function execute()
    {
        return 42;
    }
}
