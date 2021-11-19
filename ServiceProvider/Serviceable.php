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

interface Serviceable
{
    /**
     * Other services, extensions, callbacks, etc. that need
     * to be loaded after the called provider is booted.
     */
    public function extensions(): void;
}
