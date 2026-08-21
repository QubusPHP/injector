<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class ProvTestNoDefinitionNullDefaultClass extends stdClass
{
    public function __construct($arg = null)
    {
        $this->arg = $arg;
    }
}
