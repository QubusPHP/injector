<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class InjectorTestCtorParamWithNoTypehintOrDefaultDependent
{
    private $param;

    public function __construct(TestNoExplicitDefine $param)
    {
        $this->param = $param;
    }
}
