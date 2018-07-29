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

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UriInterface;

/**
 * Represents a HTTP Request message.
 * @package Nex\Http
 */
class Request extends Message implements RequestInterface
{
    /** @var string */
    protected $method = 'GET';
    /** @var string */
    protected $target;
    /** @var UriInterface */
    protected $uri;

    /**
     * HTTP Request message.
     * @param UriInterface|string $uri
     * @param StreamInterface|resource|string $body
     */
    public function __construct($uri = '', $body = '')
    {
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->body = $body instanceof StreamInterface ? $body : new Stream($body);
    }

    /**
     * Retrieves the HTTP method of the request.
     * @return string
     */
    public function getMethod()
    {
        return $this->method;
    }

    /**
     * Retrieves the message's request target.
     * @return string
     */
    public function getRequestTarget()
    {
        if (!is_null($this->target)) {
            return $this->target;
        }

        $origin = '/' . ltrim($this->uri->getPath(), '/');
        if (!empty($query = $this->uri->getQuery())) {
            $origin .= '?' . $query;
        }
        return $origin;
    }

    /**
     * Retrieves the URI instance.
     * @return UriInterface
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * Return an instance with the provided HTTP method.
     * @param string $method
     * @return static
     */
    public function withMethod($method)
    {
        if (!preg_match("/^[!#$%&'*+.^_`\|~0-9a-z-]+$/i", $method = strval($method))) {
            throw new \InvalidArgumentException(sprintf(
                "The given method '%s' is not a valid HTTP method.", $method
            ));
        }

        $cloned = clone $this;
        $cloned->method = $method;
        return $cloned;
    }

    /**
     * Return an instance with the specific request-target.
     * @param mixed $requestTarget
     * @return static
     */
    public function withRequestTarget($requestTarget)
    {
        if (!preg_match('#\s#', $requestTarget = strval($requestTarget))) {
            throw new \InvalidArgumentException(
                "The given request-target is not valid, cannot contain whitespace"
            );
        }

        $cloned = clone $this;
        $cloned->target = $requestTarget;
        return $cloned;
    }

    /**
     * Returns an instance with the provided URI.
     * @param UriInterface $uri
     * @param bool $preserveHost
     * @return static
     */
    public function withUri(UriInterface $uri, $preserveHost = false)
    {
        $cloned = clone $this;
        $cloned->uri = $uri;
        if (empty($uri->getHost()) || ($preserveHost && $cloned->hasHeader('host'))) {
            return $cloned;
        }

        $newhost = $uri->getHost();
        if (!is_null($port = $uri->getPort())) {
            $newhost .= ":{$port}";
        }
        return $cloned->withHeader('host', $newhost);
    }
}