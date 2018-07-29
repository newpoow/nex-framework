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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;
use Psr\Http\Message\UploadedFileInterface;
use Psr\Http\Message\UriInterface;

/**
 * Represents a HTTP Request message on the server.
 * @package Nex\Http
 */
class ServerRequest extends Request implements ServerRequestInterface
{
    /** @var array */
    protected $attributes = array();
    /** @var array */
    protected $cookies = array();
    /** @var array */
    protected $files = array();
    /** @var array|\object|null */
    protected $data;
    /** @var array */
    protected $query = array();
    /** @var array */
    protected $server = array();

    /**
     * HTTP Request message on the server.
     * @param array $serverParameters
     * @param UriInterface|string $uri
     * @param StreamInterface|string $body
     */
    public function __construct(array $serverParameters = [], $uri = '', ?StreamInterface $body = null)
    {
        $this->server = $serverParameters;
        $body = $body ?: new Stream(fopen('php://input', 'rb'));

        parent::__construct($uri, $body);
    }

    /**
     * Retrieve a single derived request attribute.
     * @param string $name
     * @param mixed $default
     * @return mixed
     */
    public function getAttribute($name, $default = null)
    {
        if (array_key_exists($name, $this->attributes)) {
            return $this->attributes[$name];
        }
        return $default;
    }

    /**
     * Retrieve attributes derived from the request.
     * @return array
     */
    public function getAttributes()
    {
        return $this->attributes;
    }

    /**
     * Retrieve cookies.
     * @return array
     */
    public function getCookieParams()
    {
        return $this->cookies;
    }

    /**
     * Retrieve any parameters provided in the request body.
     * @return array|\object|null
     */
    public function getParsedBody()
    {
        return $this->data;
    }

    /**
     * Retrieve query string arguments.
     * @return array
     */
    public function getQueryParams()
    {
        return $this->query;
    }

    /**
     * Retrieve server parameters.
     * @return array
     */
    public function getServerParams()
    {
        return $this->server;
    }

    /**
     * Retrieve normalized file upload data.
     * @return array
     */
    public function getUploadedFiles()
    {
        return $this->files;
    }

    /**
     * Return an instance with the specified derived request attribute.
     * @param string $name
     * @param mixed $value
     * @return static
     */
    public function withAttribute($name, $value)
    {
        $cloned = clone $this;
        $cloned->attributes[$name] = $value;
        return $cloned;
    }

    /**
     * Return an instance with the specified cookies.
     * @param array $cookies
     * @return static
     */
    public function withCookieParams(array $cookies)
    {
        $cloned = clone $this;
        $cloned->cookies = $cookies;
        return $cloned;
    }

    /**
     * Return an instance that removes the specified derived request attribute.
     * @param string $name
     * @return static
     */
    public function withoutAttribute($name)
    {
        $cloned = clone $this;
        unset($cloned->attributes[$name]);
        return $cloned;
    }

    /**
     * Return an instance with the specified body parameters.
     * @param array|\object|null $data
     * @return static
     */
    public function withParsedBody($data)
    {
        if (!is_array($data) && !is_object($data) && !is_null($data)) {
            throw new \InvalidArgumentException(
                "The given data is not valid, must be a array, a object or null."
            );
        }

        $cloned = clone $this;
        $cloned->data = $data;
        return $cloned;
    }

    /**
     * Return an instance with the specified query string arguments.
     * @param array $query
     * @return static
     */
    public function withQueryParams(array $query)
    {
        $cloned = clone $this;
        $cloned->query = $query;
        return $cloned;
    }

    /**
     * Create a new instance with the specified uploaded files.
     * @param array $uploadedFiles
     * @return static
     */
    public function withUploadedFiles(array $uploadedFiles)
    {
        array_walk_recursive($uploadedFiles, function ($file) {
            if (!$file instanceof UploadedFileInterface) {
                throw new \InvalidArgumentException("Invalid uploaded files structure");
            }
        });

        $cloned = clone $this;
        $cloned->files = $uploadedFiles;
        return $cloned;
    }
}