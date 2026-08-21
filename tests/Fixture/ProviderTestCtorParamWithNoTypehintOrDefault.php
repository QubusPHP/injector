<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ProviderTestCtorParamWithNoTypehintOrDefault implements TestNoExplicitDefine
{
    public $val = 42;

    public function __construct($val)
    {
        $this->val = $val;
    }
}
