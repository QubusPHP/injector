<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ConcreteExecute extends BaseExecute
{
    public function process()
    {
        return "Concrete";
    }
}
