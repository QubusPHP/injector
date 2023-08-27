<?php

declare(strict_types=1);

namespace Qubus\Injector\Tests\ServiceProvider;

interface Model
{
    public function userName(): Identity;
}
