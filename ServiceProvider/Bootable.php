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

interface Bootable
{
    /**
     * Register services that need to be loaded during
     * the booting stage.
     *
     * @return void
     */
    public function boot(): void;
}