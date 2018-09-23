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
namespace Nex\Standard\Http;

/**
 * Standardization of an access route compiler.
 * @package Nex\Http
 */
interface RouteCompilerInterface
{
    /**
     * Compile a route sequence for a regular expression.
     * @param string $routeUri
     * @param array $patterns
     * @return string
     */
    public function compile(string $routeUri, array $patterns = []): string;

    /**
     * Construct a URI using a route sequence and parameters.
     * @param string $routeUri
     * @param array $parameters
     * @return string
     */
    public function reverse(string $routeUri, array $parameters = []): string;
}