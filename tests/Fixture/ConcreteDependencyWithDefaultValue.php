<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class ConcreteDependencyWithDefaultValue
{
    public $dependency;

    public function __construct(?stdClass $instance = null)
    {
        $this->dependency = $instance;
    }
}
