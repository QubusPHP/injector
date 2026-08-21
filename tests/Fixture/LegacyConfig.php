<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Config\Config;

final class LegacyConfig implements Config
{
    /**
     * @param string $key
     * @param mixed|null $default
     * @return string|array
     */
    public function get(string $key, mixed $default = null): string|array
    {
        return [];
    }

    public function has(string $key): bool
    {
        return false;
    }
}
