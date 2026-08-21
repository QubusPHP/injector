<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ExecuteClassDepsWithMethodDeps
{
    public function __construct(TestDependency $testDep)
    {
    }

    public function execute(TestDependency $dep, $arg = null)
    {
        return $arg ?? 42;
    }
}
