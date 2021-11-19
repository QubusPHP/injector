<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2021 Joshua Parker <josh@joshuaparker.blog>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      2.0.0
 */

declare(strict_types=1);

namespace Qubus\Injector\ServiceProvider;

use Qubus\Config\ConfigContainer;
use Qubus\Injector\ReflectionContainer;

abstract class BaseServiceProvider implements Bootable, Serviceable
{
    public function __construct(
        protected ReflectionContainer $reflector,
        protected ConfigContainer $config
    ) {
    }

    /**
     * {@inheritDoc}
     */
    public function provides(): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function extensions(): void
    {
    }
}
