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

use Psr\Http\Message\UriInterface;

/**
 * Represents a Uniform Resource Identifier - URI.
 * @package Nex\Http
 */
class Uri implements UriInterface
{
    /** @var string */
    protected $fragment = '';
    /** @var string */
    protected $host = '';
    /** @var string */
    protected $path = '';
    /** @var int */
    protected $port;
    /** @var string */
    protected $query = '';
    /** @var string */
    protected $scheme = '';
    /** @var string */
    protected $userinfo = '';

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Uniform Resource Identifier - URI.
     * @param string $uri
     */
    public function __construct(string $uri = '')
    {
        if (!empty($uri)) {
            $this->parseUri($uri);
        }
    }

    /**
     * Retrieve the authority component of the URI.
     * @return string
     */
    public function getAuthority()
    {
        $authority = $this->getHost();
        if (empty($authority)) {
            return '';
        }

        if (!empty($info = $this->getUserInfo())) {
            $authority = "{$info}@{$authority}";
        }

        if (!is_null($port = $this->getPort())) {
            $authority = "{$authority}:{$port}";
        }
        return $authority;
    }

    /**
     * Retrieve the fragment component of the URI.
     * @return string
     */
    public function getFragment()
    {
        return $this->fragment;
    }

    /**
     * Retrieve the host component of the URI.
     * @return string
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * Retrieve the path component of the URI.
     * @return string
     */
    public function getPath()
    {
        return $this->path;
    }

    /**
     * Retrieve the port component of the URI.
     * @return int|null
     */
    public function getPort()
    {
        $scheme = $this->getScheme();
        if ((80 === $this->port && 'http' === $scheme) || (443 === $this->port && 'https' === $scheme)) {
            return null;
        }
        return $this->port;
    }

    /**
     * Retrieve the query string of the URI.
     * @return string
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Retrieve the scheme component of the URI.
     * @return string
     */
    public function getScheme()
    {
        return $this->scheme;
    }

    /**
     * Retrieve the user information component of the URI.
     * @return string
     */
    public function getUserInfo()
    {
        return $this->userinfo;
    }

    /**
     * Return an instance with the specified URI fragment.
     * @param string $fragment
     * @return static
     */
    public function withFragment($fragment)
    {
        if ($fragment && (0 === stripos($fragment, '#'))) {
            $fragment = '%23' . substr($fragment, 1);
        }

        $cloned = clone $this;
        $cloned->fragment = $this->normalizeQueryOrFragment($fragment);
        return $cloned;
    }

    /**
     * Return an instance with the specified host.
     * @param string $host
     * @return static
     */
    public function withHost($host)
    {
        $cloned = clone $this;
        $cloned->host = strtolower($host);
        return $cloned;
    }

    /**
     * Return an instance with the specified path.
     * @param string $path
     * @return static
     */
    public function withPath($path)
    {
        if (false !== stripos($path, '?') || false !== stripos($path, '#')) {
            throw new \InvalidArgumentException(sprintf(
                "The path '%s' must not contain query parameters or hash fragment.", $path
            ));
        }

        $cloned = clone $this;
        $cloned->path = $this->normalizePath($path);
        return $cloned;
    }

    /**
     * Return an instance with the specified port.
     * @param int|null $port
     * @return static
     */
    public function withPort($port)
    {
        $port = !is_null($port) ? intval($port) : null;
        if (is_int($port) && ($port < 1 || $port > 65535)) {
            throw new \InvalidArgumentException(sprintf(
                "Invalid port: '%s'. Must be a valid integer within TCP/UDP port range", $port
            ));
        }

        $cloned = clone $this;
        $cloned->port = $port;
        return $cloned;
    }

    /**
     * Return an instance with the specified query string.
     * @param string $query
     * @return static
     */
    public function withQuery($query)
    {
        if (false !== stripos($query, '#')) {
            throw new \InvalidArgumentException(sprintf(
                "The query '%s' must not contain hash fragment.", $query
            ));
        }

        $cloned = clone $this;
        $cloned->query = $this->normalizeQueryOrFragment($query);
        return $cloned;
    }

    /**
     * Return an instance with the specified scheme.
     * @param string $scheme
     * @return static
     */
    public function withScheme($scheme)
    {
        if ($scheme && !preg_match('/^(?:[A-Za-z][0-9A-Za-z\+\-\.]*)?$/', $scheme)) {
            throw new \InvalidArgumentException(sprintf(
                "The scheme '%s' is invalid.", $scheme
            ));
        }

        $cloned = clone $this;
        $cloned->scheme = strtolower($scheme);
        return $cloned;
    }

    /**
     * Return an instance with the specified user information.
     * @param string $user
     * @param string|null $password
     * @return static
     */
    public function withUserInfo($user, $password = null)
    {
        if (is_string($password) && !empty($password)) {
            $user .= ':' . $password;
        }

        $cloned = clone $this;
        $cloned->userinfo = $user;
        return $cloned;
    }

    /**
     * Return the string representation as a URI reference.
     * @return string
     */
    public function __toString()
    {
        $uri = '';
        if (!empty($scheme = $this->getScheme())) {
            $uri .= $scheme . ':';
        }

        if (!empty($authority = $this->getAuthority())) {
            $uri .= '//' . $authority;
        }

        if (!empty($path = $this->getPath())) {
            $uri .= $path;
        }

        if (!empty($query = $this->getQuery())) {
            $uri .= '?' . $query;
        }

        if (!empty($fragment = $this->getFragment())) {
            $uri .= '#' . $fragment;
        }
        return $uri;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Filters the path of a URI to ensure it is properly encoded.
     * @param string $path
     * @return string
     */
    protected function normalizePath(string $path): string
    {
        $pattern = '#(?:[^a-zA-Z0-9_\-\.~\pL:@&=\+\$,\/;%]+|%(?![A-Fa-f0-9]{2}))#u';
        $path = preg_replace_callback($pattern, function ($matches) {
            return rawurlencode($matches[0]);
        }, $path);

        if ($path && ('/' === $path[0])) {
            $path = ('/' . ltrim($path, '/'));
        }
        return $path;
    }

    /**
     * Filter a query string key or value, or a fragment.
     * @param string $queryOrFragment
     * @return string
     */
    protected function normalizeQueryOrFragment(string $queryOrFragment): string
    {
        $pattern = '#(?:[^a-zA-Z0-9_\-\.~\pL!\$&\'\(\)\*\+,;=%:@\/\?]+|%(?![A-Fa-f0-9]{2}))#u';
        return preg_replace_callback($pattern, function ($matches) {
            return rawurlencode($matches[0]);
        }, $queryOrFragment);
    }

    /**
     * Analyze a URI in its parts and define the properties.
     * @param string $uri
     */
    protected function parseUri(string $uri)
    {
        if (!is_array($parsed = parse_url($uri))) {
            throw new \InvalidArgumentException(sprintf(
                "The source URI string appears to be malformed: '%s'.", $uri
            ));
        }

        if (array_key_exists('scheme', $parsed)) {
            $this->scheme = strtolower($parsed['scheme']);
        }

        if (array_key_exists('user', $parsed)) {
            $this->userinfo = array_key_exists('pass', $parsed) ?
                "{$parsed['user']}:{$parsed['pass']}" : $parsed['user'];
        }

        if (array_key_exists('host', $parsed)) {
            $this->host = strtolower($parsed['host']);
        }

        if (array_key_exists('port', $parsed)) {
            $this->port = $parsed['port'];
        }

        if (array_key_exists('path', $parsed)) {
            $this->path = $this->normalizePath($parsed['path']);
        }

        if (array_key_exists('query', $parsed)) {
            $this->query = $this->normalizeQueryOrFragment($parsed['query']);
        }

        if (array_key_exists('fragment', $parsed)) {
            $this->fragment = $this->normalizeQueryOrFragment($parsed['fragment']);
        }
    }
}