<?php

declare(strict_types=1);

namespace Qubus\Injector\Tests\ServiceProvider;

class Person implements Identity
{
    public function __construct(protected ?string $userName = null)
    {
    }
}
