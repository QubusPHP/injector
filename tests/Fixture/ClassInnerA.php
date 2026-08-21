<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ClassInnerA
{
    public $dep;

    public function __construct(ClassInnerB $dep)
    {
        $this->dep = $dep;
    }
}
