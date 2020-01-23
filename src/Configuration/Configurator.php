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
namespace Nex\Configuration;

use ArrayAccess;
use ArrayIterator;
use Countable;
use InvalidArgumentException;
use IteratorAggregate;
use JsonSerializable;
use Nex\Configuration\Exceptions\ParserException;
use Nex\Standard\Configuration\ConfiguratorInterface;
use Nex\Standard\Configuration\ParserInterface;

/**
 * Configuration manager.
 * @package Nex\Configuration
 */
class Configurator implements ArrayAccess, ConfiguratorInterface, Countable, IteratorAggregate, JsonSerializable
{
    /** @var array */
    protected $items = array();
    /** @var array */
    protected $parsers = array();
    /** @var string|null */
    protected $separator;

    /**
     * Configurator constructor.
     * @param array $items
     * @param string|null $separator
     */
    public function __construct(array $items = [], ?string $separator = null)
    {
        $this->separator = $separator;
        $this->items = $this->normalize($items);
    }

    /**
     * Add a file parser.
     * @param ParserInterface $parser
     * @param array $extensions
     * @return static
     */
    public function addParser(ParserInterface $parser, array $extensions): self
    {
        foreach ($extensions as $extension) {
            $this->parsers[strtolower($extension)] = $parser;
        }
        return $this;
    }

    /**
     * Get all settings loaded.
     * @return array
     */
    public function all(): array
    {
        return $this->items;
    }

    /**
     * Get configuration data.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null)
    {
        $data = $this->items;
        if ($this->separator) {
            $key = explode($this->separator, rtrim($key, $this->separator));
        }

        foreach ((array)$key as $step) {
            if (!is_array($data) || !array_key_exists($step, $data)) {
                return $default;
            }
            $data = &$data[$step];
        }
        return $data;
    }

    /**
     * Get a parser for a file extension.
     * @param string $extension
     * @return ParserInterface
     */
    public function getParser(string $extension): ParserInterface
    {
        $extension = strtolower($extension);
        if (!array_key_exists($extension, $this->parsers)) {
            throw new InvalidArgumentException(sprintf(
                "There is no parser for the '%s' extension.", $extension
            ));
        }
        return $this->parsers[$extension];
    }

    /**
     * Check if settings have been loaded.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool
    {
        $data = $this->items;
        if (empty($this->separator)) {
            return array_key_exists($key, $data);
        }

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
     * Load settings from files.
     * @param string|string[] ...$files
     * @return ConfiguratorInterface
     */
    public function load(...$files): ConfiguratorInterface
    {
        $files = isset($files[0]) && is_array($files[0]) ? $files[0] : $files;
        foreach ($files as $name => $file) {
            if (($path = realpath($file)) === false) {
                continue;
            }

            if (!is_string($name) || empty(trim($name))) {
                $name = pathinfo($path, PATHINFO_FILENAME);
            }

            $this->set(
                strval($name),
                $this->getParser(pathinfo($path, PATHINFO_EXTENSION))->parse($path)
            );
        }
        return $this;
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
            $data = &$this->items;

            $segments = $this->separator ? explode($this->separator, $key) : [$key];
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
     * Save settings to files.
     * @param string $file
     * @param string|null $only
     * @return static
     */
    public function save(string $file, ?string $only = null): self
    {
        $content = $this->getParser(pathinfo($file, PATHINFO_EXTENSION))->dump(
            is_null($only) ? $this->all() : $this->get($only, [])
        );

        if (file_put_contents($file, $content) === false) {
            throw new ParserException(sprintf(
                "Cannot write to file '%s'.", $file
            ));
        }
        return $this;
    }

    /**
     * Set data for settings.
     * @param string|array $key
     * @param mixed $value
     * @return ConfiguratorInterface
     */
    public function set($key, $value = null): ConfiguratorInterface
    {
        $key = is_array($key) ? $key : array(strval($key) => $value);

        $this->items = array_replace_recursive(
            $this->items,
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
        if (empty($this->separator)) {
            return $dotted;
        }

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
    ##              ARRAY ACCESS METHODS            ##
    ##----------------------------------------------##
    /**
     * Check if a given data is stored using array syntax.
     * @param string $offset
     * @return bool
     */
    public function offsetExists($offset): bool
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
     * Count the number of items in the collection.
     * @return int
     */
    public function count(): int
    {
        return count($this->items);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##          ITERATOR AGGREGATE METHODS          ##
    ##----------------------------------------------##
    /**
     * Get an iterator of the stored data.
     * @return ArrayIterator
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->items);
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##          JSON SERIALIZABLE METHODS           ##
    ##----------------------------------------------##
    /**
     * Specify the data that should be serialized to JSON.
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->items;
    }
}