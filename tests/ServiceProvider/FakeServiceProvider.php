<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\ServiceProvider;

use Closure;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;

class FakeServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->container->alias('user.model', UserModel::class)
            ->define('user.model', [':userName' => new Person('Joseph Smith')]);
    }

    public function booting(Closure $callback): void
    {
    }

    public function booted(Closure $callback): void
    {
    }

    public function publishes(array $paths, ?string $group = null): void
    {
    }

    public function pathsToPublish(?string $tag = null): array
    {
        return [];
    }

    public function callBootingCallbacks(): void
    {
    }

    public function callBootedCallbacks(): void
    {
    }
}
