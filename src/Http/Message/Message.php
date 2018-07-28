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

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Abstraction of the hypertext transfer protocol message.
 * @package Nex\Http
 */
abstract class Message implements MessageInterface
{
    /** @var StreamInterface */
    protected $body;
    /** @var string[][] */
    protected $headers = array();
    /** @var string */
    protected $version = '1.1';

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Gets the body of the message.
     * @return StreamInterface
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * Retrieves a message header value by the given case-insensitive name.
     * @param string $name
     * @return string[]
     */
    public function getHeader($name)
    {
        $header = $this->normalizeHeaderName($name);
        if (!array_key_exists($header, $this->headers)) {
            return array();
        }
        return $this->headers[$header];
    }

    /**
     * Retrieves a comma-separated string of the values for a single header.
     * @param string $name
     * @return string
     */
    public function getHeaderLine($name)
    {
        if (empty($headers = $this->getHeader($name))) {
            return '';
        }
        return implode(', ', $headers);
    }

    /**
     * Retrieves all message header values.
     * @return string[][]
     */
    public function getHeaders()
    {
        return $this->headers;
    }

    /**
     * Retrieves the HTTP protocol version as a string.
     * @return string
     */
    public function getProtocolVersion()
    {
        return $this->version;
    }

    /**
     * Checks if a header exists by the given case-insensitive name.
     * @param string $name
     * @return bool
     */
    public function hasHeader($name)
    {
        return array_key_exists(strtolower($name), $this->headers);
    }

    /**
     * Return an instance with the specified header appended with the given value.
     * @param string $name
     * @param string|string[] $value
     * @return static
     */
    public function withAddedHeader($name, $value)
    {
        if (!$this->hasHeader($name)) {
            return $this->withHeader($name, $value);
        }

        $header = $this->normalizeHeaderName($name);
        $cloned = clone $this;
        $cloned->headers[$header] = array_merge($this->headers[$header], $this->normalizeHeaderValues(
            is_array($value) ? $value : array($value)
        ));
        return $cloned;
    }

    /**
     * Return an instance with the specified message body.
     * @param StreamInterface $body
     * @return static
     */
    public function withBody(StreamInterface $body)
    {
        $cloned = clone $this;
        $cloned->body = $body;
        return $cloned;
    }

    /**
     * Return an instance with the provided value replacing the specified header.
     * @param string $name
     * @param string|string[] $value
     * @return static
     */
    public function withHeader($name, $value)
    {
        $cloned = clone $this;
        $cloned->headers[$this->normalizeHeaderName($name)] = $this->normalizeHeaderValues(
            is_array($value) ? $value : array($value)
        );
        return $cloned;
    }

    /**
     * Return an instance without the specified header.
     * @param string $name
     * @return static
     */
    public function withoutHeader($name)
    {
        $cloned = clone $this;
        unset($cloned->headers[$this->normalizeHeaderName($name)]);
        return $cloned;
    }

    /**
     * Return an instance with the specified HTTP protocol version.
     * @param string $version
     * @return static
     */
    public function withProtocolVersion($version)
    {
        if (!preg_match('/^\d(?:\.\d)?$/', $version = strval($version))) {
            throw new \InvalidArgumentException(sprintf(
                "The given protocol version '%s' is not valid; use the format: <major>.<minor> numbering scheme",
                $version
            ));
        }

        $cloned = clone $this;
        $cloned->version = $version;
        return $cloned;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Normalizes the given header name.
     * @param string $header
     * @return string
     */
    protected function normalizeHeaderName(string $header): string
    {
        if (!preg_match("@^[a-zA-Z0-9'`#$%&*+.^_|~!-]+$@", $header)) {
            throw new \InvalidArgumentException(sprintf(
                "The given header name '%s' is not valid; must be an RFC 7230 compatible string.",
                $header
            ));
        }
        return strtolower($header);
    }

    /**
     * Normalizes the values for the headers.
     * @param array $headers
     * @return array
     */
    protected function normalizeHeaderValues(array $headers): array
    {
        if (empty($headers)) {
            throw new \InvalidArgumentException(
                "Header values must be an array of strings, cannot be an empty array."
            );
        }

        return array_map(function (string $header) {
            if (preg_match("#(?:(?:(?<!\r)\n)|(?:\r(?!\n))|(?:\r\n(?![ \t])))#", $header)
                || preg_match('/[^\x09\x0a\x0d\x20-\x7E\x80-\xFE]/', $header)) {
                throw new \InvalidArgumentException(sprintf(
                    "The given header value '%s' is not valid", $header
                ));
            }
            return $header;
        }, array_values($headers));
    }
}