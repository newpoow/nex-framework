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
namespace Nex\Http\Message;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * HTTP Response Factory.
 * @package Nex\Http
 */
class ResponseFactory implements ResponseFactoryInterface
{
    /**
     * Create a new response.
     * @param int $code
     * @param string $reasonPhrase
     * @return ResponseInterface
     */
    public function createResponse(int $code = 200, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response())->withStatus($code, $reasonPhrase);
    }
}