<?php

/**
 * Qubus\Injector
 *
 * @link       https://github.com/QubusPHP/injector
 * @copyright  2020 Joshua Parker <joshua@joshuaparker.dev>
 * @copyright  2013-2014 Daniel Lowrey, Levi Morrison, Dan Ackroyd
 * @license    https://opensource.org/licenses/mit-license.php MIT License
 */

declare(strict_types=1);

namespace Qubus\Injector\Psr11;

use Psr\Container\ContainerInterface;
use Qubus\Exception\Exception;
use Qubus\Exception\Http\Client\NotFoundException;
use Qubus\Injector\Injector;
use Qubus\Injector\ServiceContainer;
use ReflectionClass;

use function array_filter;
use function class_exists;
use function sprintf;

class Container extends Injector implements ContainerInterface, ServiceContainer
{
    protected array $has = [];

    /**
     * {@inheritDoc}
     * @throws NotFoundException
     */
    public function get(string $id)
    {
        if (! $this->has($id)) {
            throw new NotFoundException(
                sprintf('No entry found: %s', $id)
            );
        }

        try {
            return $this->make($id);
        } catch (Exception $previous) {
            throw new ContainerException(
                sprintf('Unable to get: %s', $id),
                0,
                $previous
            );
        }
    }

    /**
     * {@inheritDoc}
     */
    public function has(string $id): bool
    {
        static $filter = Injector::I_BINDINGS
        | Injector::I_DELEGATES
        | Injector::I_PREPARES
        | Injector::I_ALIASES
        | Injector::I_SHARES;

        if (isset($this->has[$id])) {
            return $this->has[$id];
        }

        $definitions = array_filter($this->inspect($id, $filter));
        if (! empty($definitions)) {
            return $this->has[$id] = true;
        }

        if (! class_exists($id)) {
            return $this->has[$id] = false;
        }

        $reflector = new ReflectionClass($id);
        if ($reflector->isInstantiable()) {
            return $this->has[$id] = true;
        }

        return $this->has[$id] = false;
    }
}
