<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class RecursiveClass2
{
    public function __construct(RecursiveClass1 $dep)
    {
    }
}
