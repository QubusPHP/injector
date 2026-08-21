<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class TestMissingDependency
{
    public function __construct(TypoInTypehint $class)
    {
    }
}
