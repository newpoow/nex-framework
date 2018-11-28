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
 * Standardization of a file parser.
 * @package Nex\Configuration
 */
interface ParserInterface
{
    /**
     * Parse a file and get its contents.
     * @param string $file
     * @return array
     */
    public function parse(string $file): array;

    /**
     * Standardize data to be saved.
     * @param array $data
     * @return string
     */
    public function dump(array $data): string;
}