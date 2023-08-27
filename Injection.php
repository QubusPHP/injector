<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2020 Joshua Parker <joshua@joshuaparker.dev>
 * @copyright  2013-2014 Daniel Lowrey, Levi Morrison, Dan Ackroyd
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Injector;

class Injection
{
    /**
     * Instantiate an Injection object.
     *
     * @param string $alias Alias that should be instantiated.
     */
    public function __construct(protected string $alias)
    {
    }

    /**
     * Get the alias that should be instantiated.
     *
     * @return string Alias that should be instantiated.
     */
    public function getAlias(): string
    {
        return $this->alias;
    }
}
