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

interface InjectorException
{
    public const E_NON_EMPTY_STRING_ALIAS = 1;
    public const M_NON_EMPTY_STRING_ALIAS = 'Invalid alias: non-empty string required at arguments 1 and 2';
    public const E_SHARED_CANNOT_ALIAS    = 2;
    public const M_SHARED_CANNOT_ALIAS    = 'Cannot alias class %s to %s because it is currently shared';
    public const E_SHARE_ARGUMENT         = 3;
    public const M_SHARE_ARGUMENT         = '%s::share() requires a string class name or object instance at Argument 1; %s specified';
    public const E_ALIASED_CANNOT_SHARE   = 4;
    public const M_ALIASED_CANNOT_SHARE   = 'Cannot share class %s because it is currently aliased to %s';
    public const E_INVOKABLE              = 5;
    public const M_INVOKABLE              = 'Invalid invokable: callable or provisional string required';
    public const E_NON_PUBLIC_CONSTRUCTOR = 6;
    public const M_NON_PUBLIC_CONSTRUCTOR = 'Cannot instantiate public/public constructor in class %s';
    public const E_NEEDS_DEFINITION       = 7;
    public const M_NEEDS_DEFINITION       = 'Injection definition required for %s %s';
    public const E_MAKE_FAILURE           = 8;
    public const M_MAKE_FAILURE           = 'Could not make %s: %s';
    public const E_UNDEFINED_PARAM        = 9;
    public const M_UNDEFINED_PARAM        = 'No definition available to provision typeless parameter $%s at position %d in %s(). Injection Chain: %s';
    public const E_DELEGATE_ARGUMENT      = 10;
    public const M_DELEGATE_ARGUMENT      = '%s::delegate expects a valid callable or executable class::method string at Argument 2%s';
    public const E_CYCLIC_DEPENDENCY      = 11;
    public const M_CYCLIC_DEPENDENCY      = 'Detected a cyclic dependency while provisioning %s';
    public const E_MAKING_FAILED          = 12;
    public const M_MAKING_FAILED          = 'Making %s did not result in an object, instead result is of type \'%s\'';
}
