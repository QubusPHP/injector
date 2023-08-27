<?php

declare(strict_types=1);

namespace Qubus\Injector\Tests\ServiceProvider;

class UserModel implements Model
{
    public function __construct(
        protected ?Identity $userName = null
    ) {
    }

    public function userName(): Identity
    {
        return $this->userName;
    }
}
