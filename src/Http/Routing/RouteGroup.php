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

/**
 * Route group with common attributes.
 * @package Nex\Http
 */
class RouteGroup
{
    /** @var array */
    private $middleware = array();
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
     * @param callable|string|array $action
     * @return Route
     */
    public function createRoute(array $methods, string $uri, $action): Route
    {
        if (!is_callable($action) && isset($this->namespace)) {
            $action = $this->prependGroupNamespace($action);
        }

        $route = new Route($methods, $this->prefix($uri), $action);
        return $route->where($this->patterns)->middleware($this->middleware);
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
                throw new \LogicException(
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
     * Set the intermediate actions, which will be common in the routes.
     * @param mixed ...$middlewares
     * @return RouteGroup
     */
    protected function setMiddleware(...$middlewares): RouteGroup
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
     * Prefix the given URI with the group prefix.
     * @param string $uri
     * @return string
     */
    protected function prefix(string $uri): string
    {
        if (is_string($this->prefix)) {
            $uri = rtrim($this->prefix, '/') . '/' . trim($uri, '/');
        }
        return $uri;
    }

    /**
     * Prepend the last group namespace onto the action.
     * @param array|string $action
     * @return array|string
     */
    protected function prependGroupNamespace($action)
    {
        if (is_array($action) && is_string(reset($action))) {
            $action = strval(array_shift($action)) . '@' . strval(array_shift($action));
        }

        if (is_string($action) && strpos($action, '\\') !== 0) {
            $action = strval($this->namespace) . '\\' . $action;
        }
        return $action;
    }

    /**
     * Set a namespace, common in the routes.
     * @param string|null $namespace
     * @return RouteGroup
     */
    protected function setNamespace(?string $namespace): RouteGroup
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
     * @return RouteGroup
     */
    protected function setPrefix(string $prefix): RouteGroup
    {
        if (is_string($this->prefix)) {
            $prefix = rtrim($this->prefix, '/') . '/' . trim($prefix, '/');
        }

        $this->prefix = $prefix;
        return $this;
    }

    /**
     * Set the regular expressions, common in the routes.
     * @param array $wheres
     * @return RouteGroup
     */
    protected function setWhere(array $wheres): RouteGroup
    {
        $this->patterns = array_merge($this->patterns, $wheres);
        return $this;
    }
}