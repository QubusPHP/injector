<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class RecursiveClassC
{
    public function __construct(RecursiveClassA $a)
    {
    }
}
