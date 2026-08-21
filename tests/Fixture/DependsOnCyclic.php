<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class DependsOnCyclic
{
    public function __construct(RecursiveClassA $a)
    {
    }
}
