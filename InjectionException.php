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

use ReflectionException;
use RuntimeException;

use function array_flip;
use function array_key_exists;
use function get_class;
use function is_array;
use function is_object;
use function is_string;
use function ksort;
use function sprintf;
use function substr;

class InjectionException extends RuntimeException implements InjectorException
{
    /** @var array $dependencyChain */
    public array $dependencyChain;

    public function __construct(
        array $inProgressMakes,
        $message = "",
        $code = 0,
        ?ReflectionException $previous = null
    ) {
        $this->dependencyChain = array_flip($inProgressMakes);
        ksort($this->dependencyChain);

        parent::__construct($message, $code, $previous);
    }

    /**
     * Add a human readable version of the invalid callable to the standard 'invalid invokable' message.
     *
     * @param string|array|object $callableOrMethodStr
     */
    public static function fromInvalidCallable(
        array $inProgressMakes,
        $callableOrMethodStr,
        ?ReflectionException $previous = null
    ) {
        $callableString = null;

        if (is_string($callableOrMethodStr)) {
            $callableString .= $callableOrMethodStr;
        } elseif (
            is_array($callableOrMethodStr) &&
            array_key_exists(0, $callableOrMethodStr) &&
            array_key_exists(0, $callableOrMethodStr)
        ) {
            if (is_string($callableOrMethodStr[0]) && is_string($callableOrMethodStr[1])) {
                $callableString .= $callableOrMethodStr[0] . '::' . $callableOrMethodStr[1];
            } elseif (is_object($callableOrMethodStr[0]) && is_string($callableOrMethodStr[1])) {
                $callableString .= sprintf(
                    "[object(%s), '%s']",
                    get_class($callableOrMethodStr[0]),
                    $callableOrMethodStr[1]
                );
            }
        }

        if ($callableString) {
            // Prevent accidental usage of long strings from filling logs.
            $callableString = substr($callableString, 0, 250);
            $message = sprintf(
                "%s. Invalid callable was '%s'",
                InjectorException::M_INVOKABLE,
                $callableString
            );
        } else {
            $message = InjectorException::M_INVOKABLE;
        }

        return new static($inProgressMakes, $message, InjectorException::E_INVOKABLE, $previous);
    }

    /**
     * Returns the hierarchy of dependencies that were being created when
     * the exception occurred.
     *
     * @return array
     */
    public function getDependencyChain(): array
    {
        return $this->dependencyChain;
    }
}
