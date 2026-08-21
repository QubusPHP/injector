<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Config\InjectorConfig;

final class LegacyInjectorConfig extends InjectorConfig
{
    /**
     * @return string|array
     */
    public function get(string $key, $default = null): string|array
    {
        return parent::get($key, $default) ?? [];
    }
}
