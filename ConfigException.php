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

namespace Qubus\Injector;

use LogicException;

class ConfigException extends LogicException implements InjectorException
{
}
