<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\ServiceProvider;

interface Model
{
    public function userName(): Identity;
}
