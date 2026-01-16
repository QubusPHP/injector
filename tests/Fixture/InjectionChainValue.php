<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class InjectionChainValue
{
    public $value;

    public function __construct($value)
    {
        $this->value = $value;
    }
}
