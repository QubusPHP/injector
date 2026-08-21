<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class NonConcreteDependencyWithDefaultValue
{
    public $interface;

    public function __construct(?DelegatableInterface $i = null)
    {
        $this->interface = $i;
    }
}
