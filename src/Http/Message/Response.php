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

use InvalidArgumentException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Represents a HTTP Response message.
 * @package Nex\Http
 */
class Response extends Message implements ResponseInterface
{
    /** @var string */
    protected $phrase = 'OK';
    /** @var int */
    protected $code = 200;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * HTTP Response message.
     * @param string $body
     * @param int $code
     * @param array $headers
     */
    public function __construct($body = '', int $code = 200, array $headers = [])
    {
        $this->setStatusCode($code);
        $this->body = $body instanceof StreamInterface ? $body : new Stream($body);
        $this->headers = $this->normalizeHeaders($headers);
    }

    /**
     * Gets the response reason phrase associated with the status code.
     * @return string
     */
    public function getReasonPhrase()
    {
        return $this->phrase;
    }

    /**
     * Gets the response status code.
     * @return int
     */
    public function getStatusCode()
    {
        return $this->code;
    }

    /**
     * Return an instance with the specified status code and, optionally, reason phrase.
     * @param int $code
     * @param string $reasonPhrase
     * @return static
     */
    public function withStatus($code, $reasonPhrase = '')
    {
        $cloned = clone $this;
        $cloned->setStatusCode($code);
        if (is_string($reasonPhrase) && !empty($reasonPhrase)) {
            $cloned->phrase = $reasonPhrase;
        }
        return $cloned;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Set the status code.
     * @param int $code
     * @return static
     */
    protected function setStatusCode(int $code): self
    {
        if (!is_int($code) || !($code >= 100 && $code <= 599)) {
            throw new InvalidArgumentException(
                "The given status-code is not valid, must be an integer between 100 and 599, inclusive."
            );
        }

        $this->code = $code;
        if (isset(StatusCode::HTTP_MESSAGES[$code])) {
            $this->phrase = StatusCode::HTTP_MESSAGES[$code];
        }
        return $this;
    }
}