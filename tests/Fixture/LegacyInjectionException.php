<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\InjectionException;
use ReflectionException;

final class LegacyInjectionException extends InjectionException
{
    public function __construct(
        array $inProgressMakes,
        $message = '',
        $code = 0,
        ?ReflectionException $previous = null
    ) {
        parent::__construct($inProgressMakes, $message, $code, $previous);
    }
}
