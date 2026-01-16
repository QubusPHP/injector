<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\ServiceProvider;

class Person implements Identity
{
    public function __construct(protected ?string $userName = null)
    {
    }
}
