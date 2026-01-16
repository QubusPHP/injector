<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class InjectionChainFixture
{
    public $icv;
    public $dependency;

    public function __construct(
        InjectionChainTestDependency $ictd,
        InjectionChainValue $icv
    ) {
        $this->dependency = $ictd;
        $this->icv        = $icv;
    }
}
