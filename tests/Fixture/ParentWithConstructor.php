<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ParentWithConstructor
{
    public $foo;

    public function __construct($foo)
    {
        $this->foo = $foo;
    }
}
