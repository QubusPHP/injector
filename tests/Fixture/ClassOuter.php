<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ClassOuter
{
    public $dep;

    public function __construct(ClassInnerA $dep)
    {
        $this->dep = $dep;
    }
}
