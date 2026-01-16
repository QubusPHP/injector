<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class InjectionChainTestDependency
{
    public $icv;

    public function __construct(InjectionChainValue $icv)
    {
        $this->icv = $icv;
    }
}
