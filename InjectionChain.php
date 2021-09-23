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

use RuntimeException;

use function array_flip;
use function array_pop;
use function count;
use function is_numeric;

class InjectionChain
{
    /**
     * Store the chain of instantiations.
     *
     * @var array $chain
     */
    private array $chain;

    /**
     * Instantiate an InjectionChain object.
     *
     * @param array|null $inProgressMakes Optional. Array of instantiations.
     */
    public function __construct(array $inProgressMakes = [])
    {
        // Swap class names and indexes around.
        $this->chain = array_flip($inProgressMakes);
        // Remove the Qubus\Injector\InjectionChain class.
        array_pop($this->chain);
    }

    /**
     * Get the chain of instantiations.
     *
     * @return array Array of instantiations.
     */
    public function getChain(): array
    {
        return $this->chain;
    }

    /**
     * Get the instantiation at a specific index.
     *
     * The first (root) instantiation is 0, with each subsequent level adding 1
     * more to the index.
     *
     * Provide a negative index to step back from the end of the chain.
     * Example: `getByIndex( -2 )` will return the second-to-last element.
     *
     * @param int $index Element index to retrieve. Negative value to fetch from the end of the chain.
     * @return string|false Class name of the element at the specified index. False if index not found.
     * @throws RuntimeException If the index is not a numeric value.
     */
    public function getByIndex(int $index)
    {
        if (! is_numeric($index)) {
            throw new RuntimeException('Index needs to be a numeric value.');
        }

        $index = (int) $index;

        if ($index < 0) {
            $index += count($this->chain);
        }

        return $this->chain[$index] ?? false;
    }
}
