<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2020 Joshua Parker
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 *
 * @since      1.0.0
 */

declare(strict_types=1);

namespace Qubus\Injector\Psr11;

use Psr\Container\ContainerExceptionInterface;
use Qubus\Exception\Exception;

class ContainerException extends Exception implements ContainerExceptionInterface
{
}
