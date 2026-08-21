<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class InjectorTestParentClass extends stdClass
{
    public function __construct($arg1)
    {
        $this->arg1 = $arg1;
    }
}
