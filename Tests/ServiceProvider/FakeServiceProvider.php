<?php

declare(strict_types=1);

namespace Qubus\Injector\Tests\ServiceProvider;

use Qubus\Injector\ServiceProvider\BaseServiceProvider;

class FakeServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->container->alias('user.model', UserModel::class)
            ->define('user.model', [':userName' => new Person('Joseph Smith')]);
    }
}
