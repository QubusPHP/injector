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

namespace Qubus\Injector\Tests\ServiceProvider;

class UserModel implements Model
{
    public function __construct(
        protected ?Identity $userName = null
    ) {
    }

    public function userName(): Identity
    {
        return $this->userName;
    }
}
