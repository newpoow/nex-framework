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
namespace Nex\Configuration\Parsers;

use Nex\Configuration\Exceptions\ParserException;
use Nex\Standard\Configuration\ParserInterface;

/**
 * Parser for files with the .json extension.
 * @package Nex\Configuration
 */
class JsonParser implements ParserInterface
{
    /**
     * Parse a file and get its contents.
     * @param string $file
     * @return array
     */
    public function parse(string $file): array
    {
        if (($path = realpath($file)) === false) {
            throw new \InvalidArgumentException(sprintf(
                "Couldn't compute the absolute path of '%s'.", $file
            ));
        }

        $content = json_decode(file_get_contents($path), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ParserException(sprintf(
                "Error encountered while loading file '%s': %s", $file, json_last_error_msg()
            ));
        }
        return $content;
    }

    /**
     * Standardize data to be saved.
     * @param array $data
     * @return string
     */
    public function dump(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}