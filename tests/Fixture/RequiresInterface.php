<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use stdClass;

class RequiresInterface extends stdClass
{
    public $dep;

    public function __construct(DepInterface $dep)
    {
        $this->testDep = $dep;
    }
}
