<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ClassWithAliasAsParameter
{
    public $sharedClass;

    public function __construct(SharedClass $sharedClass)
    {
        $this->sharedClass = $sharedClass;
    }
}
