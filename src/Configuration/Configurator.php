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

use Nex\Configuration\Exceptions\ParserException;
use Nex\Standard\Configuration\ConfiguratorInterface;
use Nex\Standard\Configuration\ParserInterface;
use Nex\Support\Repository;

/**
 * Configuration manager.
 * @package Nex\Configuration
 */
class Configurator extends Repository implements ConfiguratorInterface
{
    /** @var ParserInterface[] */
    protected $parsers = array();

    /**
     * Add a file parser.
     * @param ParserInterface $parser
     * @param array $extensions
     * @return ConfiguratorInterface
     */
    public function addParser(ParserInterface $parser, array $extensions): ConfiguratorInterface
    {
        foreach ($extensions as $extension) {
            $this->parsers[strtolower($extension)] = $parser;
        }
        return $this;
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
            throw new \InvalidArgumentException(sprintf(
                "There is no parser for the '%s' extension.", $extension
            ));
        }
        return $this->parsers[$extension];
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
     * Save settings to files.
     * @param string $file
     * @param string|null $only
     * @return ConfiguratorInterface
     */
    public function save(string $file, ?string $only = null): ConfiguratorInterface
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
}