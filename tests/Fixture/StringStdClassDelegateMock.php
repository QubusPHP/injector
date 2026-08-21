<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class StringStdClassDelegateMock
{
    public function __invoke()
    {
        return $this->make();
    }

    private function make()
    {
        $obj       = new stdClass();
        $obj->test = 42;

        return $obj;
    }
}
