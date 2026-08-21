<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class RecursiveClassA
{
    public function __construct(RecursiveClassB $b)
    {
    }
}
