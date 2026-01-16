<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class ReturnsCallable
{
    private $value = 'original';

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getCallable()
    {
        return function () {
            return $this->value;
        };
    }
}
