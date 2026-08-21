<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ExecuteClassRelativeStaticMethod extends ExecuteClassStaticMethod
{
    public static function execute()
    {
        return 'this should NEVER be seen since we are testing against parent::execute()';
    }
}
