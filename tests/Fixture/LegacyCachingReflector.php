<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Cache\CachingReflector;

final class LegacyCachingReflector extends CachingReflector
{
    public const CACHE_KEY_CLASSES = 'injector.refls.classes.';
}
