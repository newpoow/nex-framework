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
namespace Nex\Http\Exceptions;

/**
 * Exception caused when route executor encounters problems processing actions.
 * @package Nex\Http
 */
class RouterHttpException extends HttpException
{
    /**
     * Router Http Exception constructor.
     * @param string $message
     * @param array $headers
     * @param \Throwable|null $previous
     */
    public function __construct(string $message, array $headers = [], \Throwable $previous = null)
    {
        parent::__construct($message, 500, $headers, $previous);
    }
}