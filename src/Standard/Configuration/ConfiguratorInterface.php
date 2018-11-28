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
namespace Nex\Standard\Configuration;

/**
 * Standardization of a system configurator.
 * @package Nex\Configuration
 */
interface ConfiguratorInterface
{
    /**
     * Add a file parser.
     * @param ParserInterface $parser
     * @param array $extensions
     * @return ConfiguratorInterface
     */
    public function addParser(ParserInterface $parser, array $extensions): ConfiguratorInterface;

    /**
     * Get all settings loaded.
     * @return array
     */
    public function all(): array;

    /**
     * Get configuration data.
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    public function get(string $key, $default = null);

    /**
     * Get a parser for a file extension.
     * @param string $extension
     * @return ParserInterface
     */
    public function getParser(string $extension): ParserInterface;

    /**
     * Check if settings have been loaded.
     * @param string $key
     * @return bool
     */
    public function has(string $key): bool;

    /**
     * Load settings from files.
     * @param string|string[] ...$files
     * @return ConfiguratorInterface
     */
    public function load(...$files): ConfiguratorInterface;

    /**
     * Remove data from settings.
     * @param string|string[] ...$keys
     */
    public function remove(...$keys);

    /**
     * Save settings to files.
     * @param string $file
     * @param string|null $only
     * @return ConfiguratorInterface
     */
    public function save(string $file, ?string $only = null): ConfiguratorInterface;

    /**
     * Set data for settings.
     * @param string|array $key
     * @param mixed $value
     */
    public function set($key, $value = null);
}