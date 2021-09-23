<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2020 Joshua Parker <josh@joshuaparker.blog>
 * @copyright  2013-2014 Daniel Lowrey, Levi Morrison, Dan Ackroyd
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Injector;

class Injection
{
    /**
     * Alias that should be instantiated.
     */
    private string $alias;

    /**
     * Instantiate an Injection object.
     *
     * @param string $alias Alias that should be instantiated.
     */
    public function __construct(string $alias)
    {
        $this->alias = $alias;
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
