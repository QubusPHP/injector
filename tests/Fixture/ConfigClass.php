<?php

declare(strict_types=1);

namespace Qubus\Injector\Test\Fixture;

use Qubus\Injector\Config\Config;

class ConfigClass
{
    //use ConfigTrait;

    public function __construct(Config $config)
    {
        //$this->processConfig($config);
    }

    public function check($key)
    {
        //return $this->getConfigKey($key);
    }
}
