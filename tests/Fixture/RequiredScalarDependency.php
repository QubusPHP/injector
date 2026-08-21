<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class RequiredScalarDependency
{
    public function __construct(public string $value)
    {
    }
}
