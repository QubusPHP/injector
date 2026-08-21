<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ExecuteClassInvokable
{
    public function __invoke()
    {
        return 42;
    }
}
