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
 * Defines the methods for creating access routes.
 * @package Nex\Http
 */
trait Routable
{
    /**
     * Define access routes for specific methods.
     * @param array|string $methods
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    abstract public function map($methods, string $uri, $action): Route;

    /**
     * Defines a route for access to all HTTP verbs.
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    public function any(string $uri, $action): Route
    {
        return $this->map('DELETE|GET|HEAD|OPTIONS|PATCH|POST|PUT', $uri, $action);
    }

    /**
     * Defines a route for access using the DELETE verb.
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    public function delete(string $uri, $action): Route
    {
        return $this->map('DELETE', $uri, $action);
    }

    /**
     * Defines a route for access using the GET verb.
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    public function get(string $uri, $action): Route
    {
        return $this->map('GET|HEAD', $uri, $action);
    }

    /**
     * Defines a route for access using the OPTIONS verb.
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    public function options(string $uri, $action): Route
    {
        return $this->map('OPTIONS', $uri, $action);
    }

    /**
     * Defines a route for access using the PATCH verb.
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    public function patch(string $uri, $action): Route
    {
        return $this->map('PATCH', $uri, $action);
    }

    /**
     * Defines a route for access using the POST verb.
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    public function post(string $uri, $action): Route
    {
        return $this->map('POST', $uri, $action);
    }

    /**
     * Defines a route for access using the PUT verb.
     * @param string $uri
     * @param callable|string|array $action
     * @return Route
     */
    public function put(string $uri, $action): Route
    {
        return $this->map('PUT', $uri, $action);
    }
}