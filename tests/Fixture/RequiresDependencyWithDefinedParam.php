<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class RequiresDependencyWithDefinedParam
{
    public $obj;

    public function __construct(DependencyWithDefinedParam $obj)
    {
        $this->obj = $obj;
    }
}
