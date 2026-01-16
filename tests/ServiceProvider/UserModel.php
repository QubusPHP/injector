<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\ServiceProvider;

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
