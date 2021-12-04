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

use Qubus\Injector\ServiceContainer;
use Qubus\Injector\ServiceProvider\BaseServiceProvider;

class FakeServiceProvider extends BaseServiceProvider
{
    public function register(): void
    {
        $this->container->alias('user.model', UserModel::class)
            ->define('user.model', [':userName' => new Person('Joseph Smith')]);
    }
}
