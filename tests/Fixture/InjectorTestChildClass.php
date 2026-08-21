<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class InjectorTestChildClass extends InjectorTestParentClass
{
    public function __construct($arg1, $arg2)
    {
        parent::__construct($arg1);
        $this->arg2 = $arg2;
    }
}
