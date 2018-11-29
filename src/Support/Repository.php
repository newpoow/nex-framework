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
 * @copyright (c) 2019 Nex Framework { https://github.com/newpoow/nex-framework }
 */
namespace Nex\Support;

/**
 * Data Repository.
 * @package Nex
 */
class Repository implements \ArrayAccess, \Countable, \IteratorAggregate, \JsonSerializable
{
    /** @var array */
    protected $data = array();
    /** @var string */
    protected $separator = '.';

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Data Repository.
     * @param array $data
     * @param string $separator
     */
    public function __construct(array $data = [], string $separator = '.')
    {
        $this->separator = $separator;
        $this->data = $this->normalize($data);
    }

    /**
     * Get all stored data.
     * @return array
     */
    public function all(): array
    {
        return $this->data;
    }

    /**
     * Get data from store.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $data = $this->data;
        foreach (explode($this->separator, $key) as $segment) {
            if (!is_array($data) || !array_key_exists($segment, $data)) {
                return $default;
            }
            $data = &$data[$segment];
        }
        return $data;
    }

    /**
     * Checks whether a given data is stored.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $data = $this->data;
        $segments = explode($this->separator, $key);
        while (count($segments) > 0) {
            $segment = array_shift($segments);
            if (!isset($data[$segment])) {
                return false;
            }
            $data = &$data[$segment];
        }
        return true;
    }

    /**
     * Remove data from the store.
     * @param string|string[] ...$keys
     * @return static
     */
    public function remove(...$keys): self
    {
        $keys = isset($keys[0]) && is_array($keys[0]) ? $keys[0] : $keys;
        foreach ($keys as $key) {
            $data = &$this->data;
            $segments = explode($this->separator, $key);

            while (count($segments) > 1) {
                $segment = array_shift($segments);
                if (!isset($data[$segment])) {
                    continue 2;
                }
                $data = &$data[$segment];
            }
            unset($data[array_shift($segments)]);
        }
        return $this;
    }

    /**
     * Add data to the store.
     * @param string|array $key
     * @param mixed $value
     * @return static
     */
    public function set($key, $value = null): self
    {
        $key = is_array($key) ? $key : array(strval($key) => $value);

        $this->data = array_replace_recursive(
            $this->data,
            $this->normalize($key)
        );
        return $this;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Convert a dot-notation array to a multidimensional array.
     * @param array $dotted
     * @return array
     */
    protected function normalize(array $dotted): array
    {
        $data = array();
        foreach ($dotted as $key => $value) {
            $value = is_array($value) ? $this->normalize($value) : $value;
            if (strpos(strval($key), $this->separator) === false) {
                $data[$key] = $value;
                continue;
            }

            $temp = &$data;
            $segments = explode($this->separator, $key);

            while (count($segments) > 0) {
                $segment = array_shift($segments);
                if (!isset($temp[$segment]) || !is_array($temp[$segment])) {
                    $temp[$segment] = array();
                }
                $temp = &$temp[$segment];
            }
            $temp = $value;
        }
        return $data;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              ARRAYACCESS METHODS             ##
    ##----------------------------------------------##
    /**
     * Check if a given data is stored using array syntax.
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset)
    {
        return $this->has($offset);
    }

    /**
     * Get data from the store using array syntax.
     * @param string $offset
     * @return mixed
     */
    public function offsetGet($offset)
    {
        return $this->get($offset);
    }

    /**
     * Add data to the store using array syntax.
     * @param string $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value)
    {
        $this->set($offset, $value);
    }

    /**
     * Remove data from the store using array syntax.
     * @param string $offset
     */
    public function offsetUnset($offset)
    {
        $this->remove($offset);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              COUNTABLE METHODS               ##
    ##----------------------------------------------##
    /**
     * Quantify the stored data.
     * @return int
     */
    public function count()
    {
        return count($this->data);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##          ITERATORAGGREGATE METHODS           ##
    ##----------------------------------------------##
    /**
     * Get an iterator of the stored data.
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->data);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##          JSONSERIALIZABLE METHODS            ##
    ##----------------------------------------------##
    /**
     * Specify the data that should be serialized to JSON.
     * @return array
     */
    public function jsonSerialize()
    {
        return $this->data;
    }
}