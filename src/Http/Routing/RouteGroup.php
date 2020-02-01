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
namespace Nex\Http\Routing;

use LogicException;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Route group with common attributes.
 * @package Nex\Http
 */
class RouteGroup
{
    /** @var array */
    protected $middleware = array();
    /** @var string */
    protected $namespace;
    /** @var string[] */
    protected $patterns = array();
    /** @var string */
    protected $prefix;

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##                PUBLIC METHODS                ##
    ##----------------------------------------------##
    /**
     * Create an instance of an access route.
     * @param array $methods
     * @param string $uri
     * @param RequestHandlerInterface $handler
     * @return Route
     */
    public function createRoute(array $methods, string $uri, RequestHandlerInterface $handler): Route
    {
        if ($this->namespace && $handler instanceof RouteHandler) {
            $handler = $handler->withPrependedNamespace($this->namespace);
        }

        $route = new Route($methods, $this->prependPrefix($uri), $handler);
        $route->where($this->patterns);
        $route->middleware($this->middleware);

        return $route;
    }

    /**
     * Create a new group, with the old and new attributes.
     * @param array $attributes
     * @return RouteGroup
     */
    public function withAttributes(array $attributes): RouteGroup
    {
        $cloned = clone $this;
        foreach ($attributes as $key => $attribute) {
            if (!is_string($key)) {
                throw new LogicException(
                    "The characteristics of the route group must be an associative array."
                );
            }

            if (in_array($key, array('middleware', 'namespace', 'prefix', 'where'))) {
                $cloned->{"set".ucwords($key)}($attribute);
            }
        }
        return $cloned;
    }

    ##++++++++++++++++++++++++++++++++++++++++++++++##
    ##              PROTECTED METHODS               ##
    ##----------------------------------------------##
    /**
     * Prefix the given URI with the group prefix.
     * @param string $uri
     * @return string
     */
    protected function prependPrefix(string $uri): string
    {
        if (is_string($this->prefix)) {
            $uri = rtrim($this->prefix, '/') . '/' . trim($uri, '/');
        }
        return $uri;
    }

    /**
     * Set the intermediate actions, which will be common in the routes.
     * @param mixed ...$middlewares
     * @return static
     */
    protected function setMiddleware(...$middlewares): self
    {
        if (isset($middlewares[0]) && is_array($middlewares[0])) {
            $middlewares = $middlewares[0];
        }

        $this->middleware = array_merge(
            array_diff($this->middleware, $middlewares), $middlewares
        );
        return $this;
    }

    /**
     * Set a namespace, common in the routes.
     * @param string $namespace
     * @return static
     */
    protected function setNamespace(string $namespace): self
    {
        if ($namespace && strpos($namespace, '\\') !== 0 && !is_null($this->namespace)) {
            $namespace = rtrim($this->namespace, '\\') . '\\' . trim($namespace, '\\');
        }
        $this->namespace = $namespace ?: null;
        return $this;
    }

    /**
     * Set a prefix for the URI, common on routes.
     * @param string $prefix
     * @return static
     */
    protected function setPrefix(string $prefix): self
    {
        if (is_string($this->prefix)) {
            $prefix = rtrim($this->prefix, '/') . '/' . trim($prefix, '/');
        }

        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the regular expressions, common in the routes.
     * @param string[] $patterns
     * @return static
     */
    protected function setWhere(array $patterns): self
    {
        $this->patterns = array_merge($this->patterns, $patterns);
        return $this;
    }
}