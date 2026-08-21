<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class CallableDelegateClassTest
{
    public function __invoke()
    {
        return new MadeByDelegate();
    }
}
