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
    /** @var string[] */
    protected $headers = array();

    /**
     * Http Exception constructor.
     * @param string $message
     * @param int $code
     * @param array $headers
     * @param Throwable|null $previous
     */
    public function __construct(string $message = "", int $code = 500, array $headers = [], Throwable $previous = null)
    {
        $this->headers = $headers;
        if (!isset(StatusCode::HTTP_MESSAGES[$code])) $code = 500;

        parent::__construct($message, $code, $previous);
    }

    /**
     * Retrieves all header values.
     * @return array
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Set response headers.
     * @param array $headers
     * @return HttpException
     */
    public function setHeaders(array $headers): self
    {
        $this->headers = $headers;
        return $this;
    }
}