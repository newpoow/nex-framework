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

use Nex\Http\Message\StatusCode;
use RuntimeException;
use Throwable;

/**
 * HTTP error exceptions.
 * @package Nex\Http
 */
abstract class HttpException extends RuntimeException
{
    /**
     * Http Exception constructor.
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 500, Throwable $previous = null)
    {
        if (!isset(StatusCode::HTTP_MESSAGES[$code])) {
            $code = 500;
        }

        if (empty($message)) {
            $message = StatusCode::HTTP_MESSAGES[$code];
        }
        parent::__construct($message, $code, $previous);
    }
}