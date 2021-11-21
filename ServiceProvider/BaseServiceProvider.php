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

use Qubus\Injector\ServiceContainer;

abstract class BaseServiceProvider implements Serviceable, Bootable
{
    /**
     * {@inheritDoc}
     */
    public function register(ServiceContainer $container): void
    {
    }

    /**
     * {@inheritDoc}
     */
    public function boot(ServiceContainer $container): void
    {
    }
}
