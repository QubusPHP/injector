<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2020 Joshua Parker <joshua@joshuaparker.dev>
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Injector\Psr11;

use Psr\Container\NotFoundExceptionInterface;
use Qubus\Exception\Http\Client\NotFoundException as LegacyNotFoundException;

class NotFoundException extends LegacyNotFoundException implements NotFoundExceptionInterface
{
}
