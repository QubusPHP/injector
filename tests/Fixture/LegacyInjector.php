<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Config\Config;
use Qubus\Injector\Injector;
use Qubus\Injector\Reflector;

final class LegacyInjector extends Injector
{
    public const I_ALL = 31;

    public function legacyConfig(): ?Config
    {
        return $this->config;
    }

    public function legacyReflector(): ?Reflector
    {
        return $this->reflector;
    }
}
