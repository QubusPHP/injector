<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Psr11\Container;

final class LegacyContainer extends Container
{
    /**
     * @return array<string, bool>
     */
    public function lookupCache(): array
    {
        return $this->has;
    }
}
