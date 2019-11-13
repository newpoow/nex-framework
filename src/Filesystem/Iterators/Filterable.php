<?php declare(strict_types=1);
/**
 * This file is part of the "Nex Framework" software,
 * A simple and efficient web framework written with PHP.
 *
 * For complete copyright and license information,
 * see the LICENSE file that was distributed with this source code.
 *
 * @license MIT
 * @author Ney Pinheiro
 * @copyright (c) 2019 Nex Framework { https://github.com/newpoow/nex-filesystem }
 */
namespace Nex\Filesystem\Iterators;

use Closure;
use FilterIterator;
use Iterator;
use OuterIterator;

/**
 * Filter the search by applying anonymous functions.
 * @package Nex\Filesystem
 */
class Filterable extends FilterIterator
{
    /** @var Closure */
    protected $filter;
    /** @var Iterator */
    protected $iterator;

    /**
     * Filter for the iterator.
     * @param Iterator $iterator
     * @param Closure $filter
     */
    public function __construct(Iterator $iterator, Closure $filter)
    {
        $this->iterator = $iterator;
        $this->filter = $filter;

        parent::__construct($iterator);
    }

    /**
     * Check whether the current element of the iterator is acceptable
     * @return bool
     */
    public function accept(): bool
    {
        $iterator = $this->iterator;
        while ($iterator instanceof OuterIterator) {
            $iterator = $iterator->getInnerIterator();
        }

        if (false === call_user_func($this->filter, $iterator)) {
            return false;
        }
        return true;
    }
}