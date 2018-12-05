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
 * @copyright (c) 2018 Nette { https://github.com/nette/finder }
 * @copyright (c) 2018 Symphony { https://github.com/symfony/finder }
 */
namespace Nex\Filesystem;

use Nex\Filesystem\Iterators\Filtrable;

/**
 * Directory and file finder using filtering rules.
 * @package Nex\Filesystem
 */
class Finder implements \Countable, \IteratorAggregate
{
    protected const ONLY_FILES = 1;
    protected const ONLY_DIRECTORIES = 2;

    /** @var array */
    protected $filters = array();
    /** @var bool */
    protected $links = false;
    /** @var int */
    protected $maxDepth = -1;
    /** @var int */
    protected $mode;
    /** @var array */
    protected $paths = array();

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Create a directory and file finder.
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    /**
     * Limits the level of recursion.
     * @param int $level
     * @return static
     */
    public function depth(int $level): self
    {
        $this->maxDepth = $level;
        return $this;
    }

    /**
     * Restricts search to directories only.
     * @return static
     */
    public function directories(): self
    {
        $this->mode = self::ONLY_DIRECTORIES;
        return $this;
    }

    /**
     * Restricts the search to files only.
     * @return static
     */
    public function files(): self
    {
        $this->mode = self::ONLY_FILES;
        return $this;
    }

    /**
     * Restricts the search using a function.
     * @param \Closure $closure
     * @return static
     */
    public function filter(\Closure $closure): self
    {
        $this->filters[] = $closure;
        return $this;
    }

    /**
     * Find by symbolic links.
     * @return static
     */
    public function followLinks(): self
    {
        $this->links = true;
        return $this;
    }

    /**
     * Search recursively from the specified directories.
     * @param string|string[] ...$paths
     * @return static
     */
    public function in(...$paths): self
    {
        if (count($this->paths) !== 0) {
            throw new \LogicException("Directory to search has already been specified.");
        }

        $this->paths = array_map(function ($path) {
            if (is_string($path) && is_dir($path)) {
                return $path;
            }

            throw new \InvalidArgumentException(sprintf(
                "The '%s' directory does not exist.", $path
            ));
        }, $paths && is_array($paths[0]) ? $paths[0] : $paths);

        return $this;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                FILTER METHODS                ##
    ##----------------------------------------------##
    /**
     * Restricts search by modification date.
     * Ex.: $finder->date('> now - 2 hours');
     * @param string $operator
     * @param string|null $date
     * @return static
     */
    public function date(string $operator, ?string $date = null): self
    {
        if (func_num_args() === 1) {
            if (!preg_match('#^(?:([=<>!]=?|<>)\s*)?(.+)\z#i', $operator, $matches)) {
                throw new \InvalidArgumentException("Invalid date predicate format.");
            }
            list(, $operator, $date) = $matches;
            $operator = $operator ?: '=';
        }

        $date = new \DateTime($date);
        return $this->filter(function (\RecursiveDirectoryIterator $iterator) use ($operator, $date) {
            return $this->compare($iterator->getMTime(), $operator, $date->format('U'));
        });
    }

    /**
     * Restricts the search by removing paths that match the masks.
     * @param string|string[] ...$pattern
     * @return static
     */
    public function exclude(...$pattern): self
    {
        $pattern = $this->buildPattern($pattern && is_array($pattern[0]) ? $pattern[0] : $pattern);
        if ($pattern) {
            array_unshift($this->filters, function (\RecursiveDirectoryIterator $iterator) use ($pattern) {
                return !preg_match($pattern, '/' . strtr($iterator->getSubPathName(), '\\', '/'));
            });
        }
        return $this;
    }

    /**
     * Restricts the search for a mask filter.
     * Ex.: $finder->name('*.php');
     * @param string|string[] ...$pattern
     * @return static
     */
    public function name(...$pattern): self
    {
        $pattern = $this->buildPattern($pattern && is_array($pattern[0]) ? $pattern[0] : $pattern);
        $this->filter(function (\RecursiveDirectoryIterator $iterator) use ($pattern) {
            return is_null($pattern) || preg_match($pattern, '/' . strtr($iterator->getSubPathName(), '\\', '/'));
        });
        return $this;
    }

    /**
     * Restricts search by size.
     * Ex.: $finder->size('> 10K');
     * @param string $operator
     * @param int|null $size
     * @return static
     */
    public function size(string $operator, ?int $size = null)
    {
        if (func_num_args() === 1) {
            if (!preg_match('#^(?:([=<>!]=?|<>)\s*)?((?:\d*\.)?\d+)\s*(K|M|G|)B?\z#i', $operator, $matches)) {
                throw new \InvalidArgumentException("Invalid size predicate format.");
            }

            list(, $operator, $size, $unit) = $matches;
            static $units = ['' => 1, 'k' => 1e3, 'm' => 1e6, 'g' => 1e9];
            $size *= $units[strtolower($unit)];
            $operator = $operator ?: '=';
        }
        return $this->filter(function (\RecursiveDirectoryIterator $iterator) use ($operator, $size) {
            return $this->compare($iterator->getSize(), $operator, $size);
        });
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Converts the Finder's pattern to regular expression.
     * @param array $masks
     * @return string|null
     */
    protected function buildPattern(array $masks): ?string
    {
        $pattern = array();
        foreach ($masks as $mask) {
            $mask = rtrim(strtr($mask, '\\', '/'), '/');
            if ($mask === '') {
                continue;
            } elseif ($mask === '*') {
                return null;
            }

            $prefix = '';
            if ($mask[0] === '/') {
                $mask = ltrim($mask, '/');
                $prefix = '(?<=^/)';
            }
            $pairs = array(
                '\*\*' => '.*', '\*' => '[^/]*', '\?' => '[^/]',
                '\[\!' => '[^', '\[' => '[', '\]' => ']', '\-' => '-'
            );
            $pattern[] = $prefix . strtr(preg_quote($mask, '#'), $pairs);
        }
        return $pattern ? '#/(' . implode('|', $pattern) . ')\z#i' : null;
    }

    /**
     * Compare two values.
     * @param mixed $left
     * @param string $operator
     * @param mixed $right
     * @return bool
     */
    protected function compare($left, string $operator, $right): bool
    {
        switch ($operator) {
            case '>': return $left > $right;
            case '<': return $left < $right;
            case '>=': return $left >= $right;
            case '<=': return $left <= $right;
            case '=':
            case '==':
                return $left == $right;
            case '!':
            case '!=':
            case '<>':
                return $left != $right;
            default:
                throw new \InvalidArgumentException("Unknown operator '{$operator}'.");
        }
    }

    /**
     * Search for occurrences that fit the filters.
     * @param string $path
     * @return \RecursiveDirectoryIterator
     */
    protected function search(string $path)
    {
        $flags = \RecursiveDirectoryIterator::SKIP_DOTS;
        if ($this->links) {
            $flags |= \RecursiveDirectoryIterator::FOLLOW_SYMLINKS;
        }

        $iterator = new \RecursiveDirectoryIterator($path, $flags);
        if ($this->maxDepth !== 0) {
            $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);
            $iterator->setMaxDepth($this->maxDepth);
        }

        if ($this->mode) {
            $iterator = new Filtrable($iterator, function (\RecursiveDirectoryIterator $iterator) {
                return (self::ONLY_DIRECTORIES === (self::ONLY_DIRECTORIES & $this->mode) && $iterator->isDir())
                    || (self::ONLY_FILES === (self::ONLY_FILES & $this->mode) && $iterator->isFile());
            });
        }

        foreach ($this->filters as $filter) {
            $iterator = new Filtrable($iterator, $filter);
        }
        return $iterator;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              COUNTABLE METHODS               ##
    ##----------------------------------------------##
    /**
     * It counts all the results collected by the iterators.
     * @return int
     */
    public function count()
    {
        return iterator_count($this->getIterator());
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##          ITERATORAGGREGATE METHODS           ##
    ##----------------------------------------------##
    /**
     * Returns an Iterator for the current configuration.
     * @return \AppendIterator
     */
    public function getIterator()
    {
        if (count($this->paths) === 0) {
            throw new \LogicException("Call in() to specify directory to search.");
        }

        $iterator = new \AppendIterator();
        foreach ($this->paths as $path) {
            $iterator->append($this->search(strval($path)));
        }
        return $iterator;
    }
}