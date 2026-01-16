<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Injector;

class CloneInjector
{
    public Injector $injector;

    public function __construct(Injector $injector)
    {
        $this->injector = clone $injector;
    }
}
