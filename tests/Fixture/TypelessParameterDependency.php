<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class TypelessParameterDependency
{
    public $thumbnailSize;

    public function __construct($thumbnailSize)
    {
        $this->thumbnailSize = $thumbnailSize;
    }
}
