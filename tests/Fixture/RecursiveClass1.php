<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class RecursiveClass1
{
    public function __construct(RecursiveClass2 $dep)
    {
    }
}
