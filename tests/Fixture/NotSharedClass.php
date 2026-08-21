<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class NotSharedClass implements SharedAliasedInterface
{
    public function foo()
    {
    }
}
