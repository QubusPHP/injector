<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Cache\ReflectionCache;

final class LegacyReflectionCache implements ReflectionCache
{
    /** @var array<string, mixed> */
    private array $values = [];

    /**
     * @return mixed|false
     */
    public function fetch(string $key)
    {
        return $this->values[$key] ?? false;
    }

    /**
     * @param mixed $data
     */
    public function store(string $key, $data)
    {
        $this->values[$key] = $data;
    }
}
