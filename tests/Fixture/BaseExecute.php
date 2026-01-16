<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

abstract class BaseExecute
{
    public function process()
    {
        return "Abstract";
    }
}
