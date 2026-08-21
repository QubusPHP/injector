<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class RequiresDependencyWithTypelessParameters
{
    public $dependency;

    public function __construct(TypelessParameterDependency $dependency)
    {
        $this->dependency = $dependency;
    }

    public function getThumbnailSize()
    {
        return $this->dependency->thumbnailSize;
    }
}
