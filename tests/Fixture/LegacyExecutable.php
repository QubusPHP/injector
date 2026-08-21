<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Executable;

final class LegacyExecutable extends Executable
{
    public function getInvocationObject()
    {
        return parent::getInvocationObject();
    }
}
