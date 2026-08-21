<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class RequiresDelegatedInterface
{
    private $interface;

    public function __construct(DelegatableInterface $interface)
    {
        $this->interface = $interface;
    }

    public function foo()
    {
        $this->interface->foo();
    }
}
