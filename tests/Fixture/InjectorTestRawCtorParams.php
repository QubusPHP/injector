<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

class InjectorTestRawCtorParams
{
    public $string;
    public $obj;
    public $int;
    public $array;
    public $float;
    public $bool;
    public $null;

    public function __construct($string, $obj, $int, $array, $float, $bool, $null)
    {
        $this->string = $string;
        $this->obj    = $obj;
        $this->int    = $int;
        $this->array  = $array;
        $this->float  = $float;
        $this->bool   = $bool;
        $this->null   = $null;
    }
}
