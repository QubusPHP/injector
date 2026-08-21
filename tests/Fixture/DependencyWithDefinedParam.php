<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class DependencyWithDefinedParam
{
    public $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }
}
